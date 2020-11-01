<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;
use App\Repositories\Front\Interfaces\CartRepositoryInterface;

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
        $items = $this->cartRepository->getContent(md5($request->user()->id));

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

        $this->catalogueRepository->checkProductInventory($product, $itemQuantity);
        
        $item = [
            'id' => md5($product->id),
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $params['qty'],
            'attributes' => $attributes,
            'associatedModel' => $product,
        ];

        if ($this->cartRepository->addItem($item, md5($request->user()->id))) {
            return $this->responseOk(true, 200, 'success');
        }

        return $this->responseError('Add item failed', 422);
    }
}
