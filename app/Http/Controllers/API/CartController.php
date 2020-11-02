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

        return $this->responseOk(ItemResource::collection($items), 200, 'Success');
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

    private function getSessionKey($request)
    {
        return md5($request->user()->id);
    }
}
