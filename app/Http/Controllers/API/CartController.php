<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;
use App\Repositories\Front\Interfaces\CartRepositoryInterface;
use App\Exceptions\OutOfStockException;

use App\Http\Resources\Item as ItemResource;

class CartController extends BaseController
{
    private $cartRepository;
    private $catalogueRepository;

    public function __construct(CartRepositoryInterface $cartRepository, CatalogueRepositoryInterface $catalogueRepository)
    {
        parent::__construct();

        $this->cartRepository = $cartRepository;
        $this->catalogueRepository = $catalogueRepository;
    }

    public function index(Request $request)
    {
        $items = $this->cartRepository->getContent($this->getSessionKey($request));

        $cart = [
            'items' => ItemResource::collection($items),
            'shipping_cost' => $this->cartRepository->getConditionValue('shipping_cost', $this->getSessionKey($request)),
            'tax_amount' => $this->cartRepository->getConditionValue('TAX 10%', $this->getSessionKey($request)),
            'total' => $this->cartRepository->getTotal($this->getSessionKey($request)),
        ];

        return $this->responseOk($cart, 200, 'Success');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sku' => ['required', 'string'],
            'qty' => ['required', 'numeric'],
            'size' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->responseError('Add item failed', 422, $validator->errors());
        }

        $params = $request->all();

        $product = $this->catalogueRepository->findBySKU($params['sku']);
        
        $attributes = [];
        if ($product->configurable()) {
            $product = $this->catalogueRepository->getProductByAttributes($product, $params);

            $attributes['size'] = $params['size'];
            $attributes['color'] = $params['color'];
        }

        $itemQuantity =  $this->cartRepository->getItemQuantity($product->id, $params['qty']);

        try {
            $this->catalogueRepository->checkProductInventory($product, $itemQuantity);
            
            $item = [
                'id' => md5($product->id),
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $params['qty'],
                'attributes' => $attributes,
                'associatedModel' => $product,
            ];

            if ($this->cartRepository->addItem($item, $this->getSessionKey($request))) {
                return $this->responseOk(true, 200, 'success');
            }
        } catch (OutOfStockException $e) {
            return $this->responseError($e->getMessage(), 400);
        }

        return $this->responseError('Add item failed', 422);
    }

    public function update(Request $request, $id)
    {
        $params = $request->all();
        $validator = Validator::make($params, [
            'qty' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return $this->responseError('Update item failed', 422, $validator->errors());
        }

        $cartItem = $this->cartRepository->getCartItem($id, $this->getSessionKey($request));

        if (!$cartItem) {
            return $this->responseError('Item not found', 404);
        }

        try {
            $this->catalogueRepository->checkProductInventory($cartItem->associatedModel, $params['qty']);

            if ($this->cartRepository->updateCart($id, $params['qty'], $this->getSessionKey($request))) {
                return $this->responseOk(true, 200, 'The item has been updated');
            }

            return $this->responseError('Update item failed', 422);
        } catch (OutOfStockException $e) {
            return $this->responseError($e->getMessage(), 400);
        }

        return $this->responseError('Update item failed', 422);
    }

    public function destroy(Request $request, $id)
    {
        $cartItem = $this->cartRepository->getCartItem($id, $this->getSessionKey($request));

        if (!$cartItem) {
            return $this->responseError('Item not found', 404);
        }

        if ($this->cartRepository->removeItem($id, $this->getSessionKey($request))) {
            return $this->responseOk(true, 200, 'The item has been deleted');
        }

        return $this->responseError('Delete item failed', 400);
    }

    public function clear(Request $request)
    {
        if ($this->cartRepository->clear($this->getSessionKey($request))) {
            return $this->responseOk(true, 200, 'The item has been cleared');
        }

        return $this->responseError('Clear cart item failed', 400);
    }

    public function shippingOptions(Request $request)
    {
        $params = $request->all();

        $validator = Validator::make($params, [
            'city_id' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return $this->responseError('Get shipping options failed', 422, $validator->errors());
        }

        try {
            $destination = $params['city_id'];
            $weight = $this->cartRepository->getTotalWeight($this->getSessionKey($request));

            return $this->responseOk($this->cartRepository->getShippingCost($destination, $weight), 200, 'success');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return $this->responseError($e->getMessage(), 400);
        }

        return $this->responseError('Get shipping options failed', 400);
    }

    public function setShipping(Request $request)
    {
        $params = $request->all();
        $validator = Validator::make($params, [
            'city_id' => ['required', 'numeric'],
            'shipping_service' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->responseError('Set shipping failed', 422, $validator->errors());
        }


        $this->cartRepository->removeConditionsByType('shipping', $this->getSessionKey($request));

        $shippingService = $request->get('shipping_service');
        $destination = $request->get('city_id');

        $shippingOptions = $this->cartRepository->getShippingCost($destination, $this->cartRepository->getTotalWeight($this->getSessionKey($request)));

        $selectedShipping = null;
        if ($shippingOptions['results']) {
            foreach ($shippingOptions['results'] as $shippingOption) {
                if (str_replace(' ', '', $shippingOption['service']) == $shippingService) {
                    $selectedShipping = $shippingOption;
                    break;
                }
            }
        }

        $status = null;
        $message = null;
        $data = [];
        if ($selectedShipping) {
            $status = 200;
            $message = 'Success set shipping cost';

            $this->cartRepository->addShippingCostToCart('shipping_cost', $selectedShipping['cost']);

            $data['total'] = number_format($this->cartRepository->getTotal());

            return $this->responseOk($data, 200, 'success');
        }

        return $this->responseError('Failed to set shipping cost', 400);
    }

    private function getSessionKey($request)
    {
        return md5($request->user()->id);
    }
}
