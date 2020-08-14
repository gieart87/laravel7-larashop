<?php
namespace App\Repositories\Front\Interfaces;

interface CartRepositoryInterface
{
    public function getContent();

    public function getItemQuantity($productID, $qtyRequested);

    public function addItem($item);

    public function getCartItem($cartID);

    public function updateCart($cartID, $qty);

    public function removeItem($cartID);
}
