<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductImageRequest;

use Auth;
use Session;
use App\Authorizable;

use App\Repositories\Admin\Interfaces\ProductRepositoryInterface;
use App\Repositories\Admin\Interfaces\AttributeRepositoryInterface;
use App\Repositories\Admin\Interfaces\CategoryRepositoryInterface;

class ProductController extends Controller
{
    use Authorizable;

    protected $productRepository;
    protected $attributeRepository;
    protected $categoryRepository;

    /**
     * Create a new controller instance.
     *
     * @param ProductRepositoryInterface   $productRepository   ProductRepositoryInterface
     * @param AttributeRepositoryInterface $attributeRepository AttributeRepositoryInterface
     * @param CategoryRepositoryInterface  $categoryRepository  CategoryRepositoryInterface
     *
     * @return void
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        AttributeRepositoryInterface $attributeRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct();

        $this->productRepository = $productRepository;
        $this->attributeRepository = $attributeRepository;
        $this->categoryRepository = $categoryRepository;

        $this->data['currentAdminMenu'] = 'catalog';
        $this->data['currentAdminSubMenu'] = 'product';

        $this->data['statuses'] = $this->productRepository->statuses();
        $this->data['types'] = $this->productRepository->types();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->data['products'] = $this->productRepository->paginate(10);

        return view('admin.products.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->data['categories'] = $this->categoryRepository->getCategoryDropdown();
        $this->data['product'] = null;
        $this->data['productID'] = 0;
        $this->data['categoryIDs'] = [];
        $this->data['configurableAttributes'] = $this->attributeRepository->getConfigurableAttributes();

        return view('admin.products.form', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ProductRequest $request params
     *
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
        $params = $request->except('_token');
        $params['user_id'] = Auth::user()->id;

        if ($product = $this->productRepository->create($params)) {
            Session::flash('success', 'Product has been saved.');
            return redirect('admin/products/'. $product->id .'/edit/');
        } else {
            Session::flash('error', 'Product could not be saved.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id product ID
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (empty($id)) {
            return redirect('admin/products/create');
        }

        $product = $this->productRepository->findById($id);
        $product->qty = isset($product->productInventory) ? $product->productInventory->qty : null;

        $this->data['categories'] = $this->categoryRepository->getCategoryDropdown();
        $this->data['product'] = $product;
        $this->data['productID'] = $product->id;
        $this->data['categoryIDs'] = $product->categories->pluck('id')->toArray();

        return view('admin.products.form', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProductRequest $request params
     * @param int            $id      product ID
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, $id)
    {
        $params = $request->except('_token');

        if ($this->productRepository->update($params, $id)) {
            Session::flash('success', 'Product has been saved');
        } else {
            Session::flash('error', 'Product could not be saved');
        }

        return redirect('admin/products');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id product id
     *
     * @return void
     */
    public function destroy($id)
    {
        if ($this->productRepository->delete($id)) {
            Session::flash('success', 'Product has been deleted');
        }

        return redirect('admin/products');
    }

    /**
     * Show product images
     *
     * @param int $id product id
     *
     * @return void
     */
    public function images($id)
    {
        if (empty($id)) {
            return redirect('admin/products/create');
        }

        $product = $this->productRepository->findById($id);

        $this->data['productID'] = $product->id;
        $this->data['productImages'] = $product->productImages;

        return view('admin.products.images', $this->data);
    }

    /**
     * Show add image form
     *
     * @param int $id product id
     *
     * @return Response
     */
    public function addImage($id)
    {
        if (empty($id)) {
            return redirect('admin/products');
        }

        $product = $this->productRepository->findById($id);

        $this->data['productID'] = $product->id;
        $this->data['product'] = $product;

        return view('admin.products.image_form', $this->data);
    }

    /**
     * Upload image
     *
     * @param ProductImageRequest $request params
     * @param int                 $id      product id
     *
     * @return Response
     */
    public function uploadImage(ProductImageRequest $request, $id)
    {
        if ($request->has('image')) {
            $image = $request->file('image');

            if ($this->productRepository->addImage($id, $image)) {
                Session::flash('success', 'Image has been uploaded.');
            } else {
                Session::flash('error', 'Image could not be uploaded.');
            }

            return redirect('admin/products/' . $id . '/images');
        }
    }

    /**
     * Remove image
     *
     * @param int $id image id
     *
     * @return Response
     */
    public function removeImage($id)
    {
        $image = $this->productRepository->findImageById($id);

        if ($this->productRepository->removeImage($id)) {
            Session::flash('success', 'Image has been deleted');
        }

        return redirect('admin/products/' . $image->product->id . '/images');
    }
}
