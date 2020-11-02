<?php
namespace App\Repositories\Front\Interfaces;

interface CartRepositoryInterface
{
    public function getContent($sessionKey = null);

    public function getItemQuantity($productID, $qtyRequested);

    public function addItem($item, $sessionKey = null);

    public function getCartItem($cartID, $sessionKey = null);

    public function updateCart($cartID, $qty, $sessionKey = null);

    public function removeItem($cartID, $sessionKey = null);

    public function isEmpty();

    public function removeConditionsByType($type);

    public function updateTax();

    public function getTotalWeight();

    public function getTotal();

    public function addShippingCostToCart($serviceName, $cost);

    public function getShippingCost($destination, $weight);

    public function clear($sessionKey = null);
}
