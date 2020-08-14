<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Str;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;

/**
 * ProductController
 *
 * PHP version 7
 *
 * @category ProductController
 * @package  ProductController
 * @author   Sugiarto <sugiarto.dlingo@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://localhost/
 */
class ProductController extends Controller
{
    private $catalogueRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CatalogueRepositoryInterface $catalogueRepository)
    {
        parent::__construct();

        $this->catalogueRepository = $catalogueRepository;

        $this->data['q'] = null;

        $this->data['categories'] = $this->catalogueRepository->getParentCategories();
        $this->data['minPrice'] = $this->catalogueRepository->getMinPrice();
        $this->data['maxPrice'] = $this->catalogueRepository->getMaxPrice();
        $this->data['colors'] = $this->catalogueRepository->getAttributeFilters('color');
        $this->data['sizes'] = $this->catalogueRepository->getAttributeFilters('size');
                                
        $this->data['sorts'] = [
            url('products') => 'Default',
            url('products?sort=price-asc') => 'Price - Low to High',
            url('products?sort=price-desc') => 'Price - High to Low',
            url('products?sort=created_at-desc') => 'Newest to Oldest',
            url('products?sort=created_at-asc') => 'Oldest to Newest',
        ];

        $this->data['selectedSort'] = url('products');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request request param
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->data['products'] = $this->catalogueRepository->paginate(9, $request);
        return $this->loadTheme('products.index', $this->data);
    }

    /**
     * Display the specified resource.
     *
     * @param string $slug product slug
     *
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $product = $this->catalogueRepository->findBySlug($slug);

        if (!$product) {
            return redirect('products');
        }

        if ($product->configurable()) {
            $this->data['colors'] = $this->catalogueRepository->getAttributeOptions($product, 'color')->pluck('text_value', 'text_value');
            $this->data['sizes'] = $this->catalogueRepository->getAttributeOptions($product, 'size')->pluck('text_value', 'text_value');
        }

        $this->data['product'] = $product;

        return $this->loadTheme('products.show', $this->data);
    }

    /**
     * Quick view product.
     *
     * @param string $slug product slug
     *
     * @return \Illuminate\Http\Response
     */
    public function quickView($slug)
    {
        $product = $this->catalogueRepository->findBySlug($slug);
        if ($product->configurable()) {
            $this->data['colors'] = $this->catalogueRepository->getAttributeOptions($product, 'color')->pluck('text_value', 'text_value');
            $this->data['sizes'] = $this->catalogueRepository->getAttributeOptions($product, 'size')->pluck('text_value', 'text_value');
        }

        $this->data['product'] = $product;
        
        return $this->loadTheme('products.quick_view', $this->data);
    }
}
