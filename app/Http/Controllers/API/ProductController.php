<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\Product as ProductResource;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;

class ProductController extends BaseController
{
    private $catalogRepository;
    private $perPage = 9;

    public function __construct(CatalogueRepositoryInterface $catalogRepository)
    {
        $this->catalogueRepository = $catalogRepository;
    }

    public function index(Request $request)
    {
        if (($perPage = (int)$request->per_page) && (int)$request->per_page <= 20) {
            $this->perPage = $perPage;
        }

        $products = $this->catalogueRepository->paginate($this->perPage, $request);

        $meta = [
            'per_page' => $this->perPage,
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage()
        ];

        return $this->responseOk(ProductResource::collection($products), 200, 'Success', $meta);
    }

    public function show($sku)
    {
        $product = $this->catalogueRepository->findBySKU($sku);

        return $this->responseOk(new ProductResource($product), 200, 'Success');
    }
}
