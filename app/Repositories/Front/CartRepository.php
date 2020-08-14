<?php

namespace App\Repositories\Front;

use App\Repositories\Front\Interfaces\CartRepositoryInterface;

use Cart;

class CartRepository implements CartRepositoryInterface
{
    public function getContent()
    {
        return $items = Cart::getContent();
    }

    public function getItemQuantity($productID, $qtyRequested)
    {
        return $this->getCartItemQuantity(md5($productID)) + $qtyRequested;
    }

    public function addItem($item)
    {
        return Cart::add($item);
    }

    public function getCartItem($cartID)
    {
        $items = Cart::getContent();

        return $items[$cartID];
    }

    public function updateCart($cartID, $qty)
    {
        return Cart::update(
            $cartID,
            [
                'quantity' => [
                    'relative' => false,
                    'value' => $qty,
                ],
            ]
        );
    }

    public function removeItem($cartID)
    {
        return Cart::remove($cartID);
    }


    // ----- Private -----
    /**
     * Get total quantity per item in the cart
     *
     * @param string $itemId item ID
     *
     * @return int
     */
    private function getCartItemQuantity($itemId)
    {
        $items = \Cart::getContent();
        $itemQuantity = 0;
        if ($items) {
            foreach ($items as $item) {
                if ($item->id == $itemId) {
                    $itemQuantity = $item->quantity;
                    break;
                }
            }
        }

        return $itemQuantity;
    }
}
