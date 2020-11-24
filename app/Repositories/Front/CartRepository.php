<?php

namespace App\Repositories\Front;

use App\Repositories\Front\Interfaces\CartRepositoryInterface;

use Cart;

class CartRepository implements CartRepositoryInterface
{
    protected $couriers = [
        'jne' => 'JNE',
        'pos' => 'POS Indonesia',
        'tiki' => 'Titipan Kilat'
    ];

    protected $rajaOngkirApiKey = null;
    protected $rajaOngkirBaseUrl = null;
    protected $rajaOngkirOrigin = null;
    
    public function __construct()
    {
        $this->rajaOngkirApiKey = env('RAJAONGKIR_API_KEY');
        $this->rajaOngkirBaseUrl = env('RAJAONGKIR_BASE_URL');
        $this->rajaOngkirOrigin = env('RAJAONGKIR_ORIGIN');
    }

    public function getContent($sessionKey = null)
    {
        if ($sessionKey) {
            $this->updateTax($sessionKey);
            return Cart::session($sessionKey)->getContent();
        }

        $this->updateTax();
        return Cart::getContent();
    }

    public function getItemQuantity($productID, $qtyRequested)
    {
        return $this->getCartItemQuantity(md5($productID)) + $qtyRequested;
    }

    public function addItem($item, $sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->add($item);
        }

        return Cart::add($item);
    }

    public function getCartItem($cartID, $sessionKey = null)
    {
        $items = $this->getContent($sessionKey);

        return !(empty($items[$cartID])) ? $items[$cartID] : null;
    }

    public function updateCart($cartID, $qty, $sessionKey = null)
    {
        $params = [
            'quantity' => [
                'relative' => false,
                'value' => $qty,
            ],
        ];

        if ($sessionKey) {
            return Cart::session($sessionKey)->update($cartID, $params);
        }

        return Cart::update($cartID, $params);
    }

    public function removeItem($cartID, $sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->remove($cartID);
        }

        return Cart::remove($cartID);
    }
    
    public function clear($sessionKey = null)
    {
        if ($sessionKey) {
            Cart::session($sessionKey)->clearCartConditions();
            return Cart::session($sessionKey)->clear();
        }

        Cart::clearCartConditions();
        return Cart::clear();
    }

    public function isEmpty($sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->isEmpty();
        }
    
        return Cart::isEmpty();
    }

    public function removeConditionsByType($type, $sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->removeConditionsByType($type);
        }

        return Cart::removeConditionsByType($type);
    }

    public function updateTax($sessionKey = null)
    {
        $condition = new \Darryldecode\Cart\CartCondition(
            [
                'name' => 'TAX 10%',
                'type' => 'tax',
                'target' => 'subtotal',
                'value' => '10%',
            ]
        );

        if ($sessionKey) {
            Cart::session($sessionKey)->removeConditionsByType('tax');
            Cart::session($sessionKey)->condition($condition);
        } else {
            Cart::removeConditionsByType('tax');
            Cart::condition($condition);
        }
    }

    public function getTotalWeight($sessionKey = null)
    {
        if ($sessionKey) {
            if (Cart::session($sessionKey)->isEmpty()) {
                return 0;
            }
        } else {
            if (Cart::isEmpty()) {
                return 0;
            }
        }

        $totalWeight = 0;
        if ($sessionKey) {
            $items = Cart::session($sessionKey)->getContent();
        } else {
            $items = Cart::getContent();
        }

        foreach ($items as $item) {
            $totalWeight += ($item->quantity * $item->associatedModel->weight);
        }

        return $totalWeight;
    }

    public function getTotalQuantity($sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->getTotalQuantity();
        }

        return Cart::getTotalQuantity();
    }

    public function getSubTotal($sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->getSubTotal();
        }

        return Cart::getSubTotal();
    }

    public function getTotal($sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->getTotal();
        }

        return Cart::getTotal();
    }

    public function getBaseTotalPrice($sessionKey = null)
    {
        $items = $this->getContent($sessionKey);

        $baseTotalPrice = 0;
        foreach ($items as $item) {
            $baseTotalPrice += $item->getPriceSum();
        }

        return $baseTotalPrice;
    }

    public function getConditionValue($name, $sessionKey = null)
    {
        if ($sessionKey) {
            return Cart::session($sessionKey)->getCondition($name);
        }

        return Cart::getCondition($name);
    }

    /**
     * Apply shipping cost to cart data
     *
     * @param string $serviceName Service name
     * @param float  $cost        Shipping cost
     *
     * @return void
     */
    public function addShippingCostToCart($serviceName, $cost)
    {
        $condition = new \Darryldecode\Cart\CartCondition(
            [
                'name' => $serviceName,
                'type' => 'shipping',
                'target' => 'total',
                'value' => '+'. $cost,
            ]
        );

        Cart::condition($condition);
    }
    
    public function getShippingCost($destination, $weight)
    {
        $params = [
            'origin' => env('RAJAONGKIR_ORIGIN'),
            'destination' => $destination,
            'weight' => $weight,
        ];

        $results = [];
        foreach ($this->couriers as $code => $courier) {
            $params['courier'] = $code;
            
            $response = $this->rajaOngkirRequest('cost', $params, 'POST');
            
            if (!empty($response['rajaongkir']['results'])) {
                foreach ($response['rajaongkir']['results'] as $cost) {
                    if (!empty($cost['costs'])) {
                        foreach ($cost['costs'] as $costDetail) {
                            $serviceName = strtoupper($cost['code']) .' - '. $costDetail['service'];
                            $costAmount = $costDetail['cost'][0]['value'];
                            $etd = $costDetail['cost'][0]['etd'];

                            $result = [
                                'service' => $serviceName,
                                'cost' => $costAmount,
                                'etd' => $etd,
                                'courier' => $code,
                            ];

                            $results[] = $result;
                        }
                    }
                }
            }
        }

        $response = [
            'origin' => $params['origin'],
            'destination' => $destination,
            'weight' => $weight,
            'results' => $results,
        ];
        
        return $response;
    }

    /**
     * Raja Ongkir Request (Shipping Cost Calculation)
     *
     * @param string $resource resource url
     * @param array  $params   parameters
     * @param string $method   request method
     *
     * @return json
     */
    public function rajaOngkirRequest($resource, $params = [], $method = 'GET')
    {
        $client = new \GuzzleHttp\Client();

        $headers = ['key' => $this->rajaOngkirApiKey];
        $requestParams = [
            'headers' => $headers,
        ];

        $url = $this->rajaOngkirBaseUrl . $resource;
        if ($params && $method == 'POST') {
            $requestParams['form_params'] = $params;
        } elseif ($params && $method == 'GET') {
            $query = is_array($params) ? '?'.http_build_query($params) : '';
            $url = $this->rajaOngkirBaseUrl . $resource . $query;
        }
        
        $response = $client->request($method, $url, $requestParams);

        return json_decode($response->getBody(), true);
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
