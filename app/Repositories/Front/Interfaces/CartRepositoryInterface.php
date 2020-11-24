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

    public function isEmpty($sessionKey = null);

    public function removeConditionsByType($type, $sessionKey = null);

    public function updateTax($sessionKey = null);

    public function getTotalWeight($sessionKey = null);

    public function getTotalQuantity($sessionKey = null);

    public function getTotal($sessionKey = null);

    public function getBaseTotalPrice($sessionKey = null);

    public function addShippingCostToCart($serviceName, $cost);

    public function getShippingCost($destination, $weight);

    public function getConditionValue($name, $sessionKey);

    public function clear($sessionKey = null);
}
