<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Repositories\Front\Interfaces\CartRepositoryInterface;
use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;

/**
 * CartController
 *
 * PHP version 7
 *
 * @category CartController
 * @package  CartController
 * @author   Sugiarto <sugiarto.dlingo@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://localhost/
 */
class CartController extends Controller
{
    private $cartRepository;
    private $catalogueRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CartRepositoryInterface $cartRepository, CatalogueRepositoryInterface $catalogueRepository)
    {
        parent::__construct();

        $this->cartRepository = $cartRepository;
        $this->catalogueRepository = $catalogueRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->data['items'] =  $this->cartRepository->getContent();

        return $this->loadTheme('carts.index', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request request form
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $params = $request->except('_token');

        $product = $this->catalogueRepository->findProductByID($params['product_id']);
        $slug = $product->slug;

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

        $this->cartRepository->addItem($item);

        \Session::flash('success', 'Product '. $item['name'] .' has been added to cart');
        return redirect('/product/'. $slug);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request request form
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $params = $request->except('_token');

        if ($items = $params['items']) {
            foreach ($items as $cartID => $item) {
                $cartItem = $this->cartRepository->getCartItem($cartID);
                $this->catalogueRepository->checkProductInventory($cartItem->associatedModel, $item['quantity']);

                $this->cartRepository->updateCart($cartID, $item['quantity']);
            }

            \Session::flash('success', 'The cart has been updated');
            return redirect('carts');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id card ID
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->cartRepository->removeItem($id);

        return redirect('carts');
    }
}
