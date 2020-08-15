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

    public function isEmpty();

    public function removeConditionsByType($type);

    public function updateTax();

    public function getTotalWeight();

    public function getTotal();

    public function addShippingCostToCart($serviceName, $cost);

    public function getShippingCost($destination, $weight);

    public function clear();
}
