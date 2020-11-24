<?php

namespace App\Repositories\Front\Interfaces;

interface OrderRepositoryInterface
{
    public function getOrders($user, $perPage);

    public function getOrder($user, $orderID);

    public function saveOrder($params, $sessionKey = null);
}
