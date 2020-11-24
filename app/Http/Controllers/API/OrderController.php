<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator;

use App\Repositories\Front\Interfaces\CartRepositoryInterface;
use App\Repositories\Front\Interfaces\OrderRepositoryInterface;
use App\Exceptions\OutOfStockException;

class OrderController extends BaseController
{
    private $cartRepository;
    private $orderRepository;

    public function __construct(CartRepositoryInterface $cartRepository, OrderRepositoryInterface $orderRepository)
    {
        parent::__construct();

        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
    }

    public function doCheckout(Request $request)
    {
        $params = $request->user()->toArray();
        $params = array_merge($params, $request->all());
        

        if ($order = $this->orderRepository->saveOrder($params, $this->getSessionKey($request))) {
            $this->cartRepository->clear($this->getSessionKey($request));
            $this->sendEmailOrderReceived($order);

            return $this->responseOk($order, 200, 'success');
        }

        return $this->responseError('Order process failed, 422');
    }

    private function getSessionKey($request)
    {
        return md5($request->user()->id);
    }

    /**
     * Send email order detail to current user
     *
     * @param Order $order order object
     *
     * @return void
     */
    private function sendEmailOrderReceived($order)
    {
        \App\Jobs\SendMailOrderReceived::dispatch($order, \Auth::user());
    }
}
