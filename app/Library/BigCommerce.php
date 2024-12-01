<?php

namespace App\Library;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;


class BigCommerce
{

    protected $client;

    public function __construct()
    {
        // Initialize the Guzzle HTTP client
        $this->client = new Client([
            'base_uri' => env('BIGCOMMERCE_API_URL') . 'stores/' . env('BIGCOMMERCE_STORE_HASH') . '/',
            'headers' => [
                // 'X-Auth-Client' => env('BIGCOMMERCE_CLIENT_ID'),
                'X-Auth-Token' => env('BIGCOMMERCE_ACCESS_TOKEN'),
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get orders from BigCommerce.
     *
     * @param array $params
     * @return array|string
     */
    public function getOrders($params = [], $orderId = null)
    {
        try {
            // Send GET request to fetch orders
            $endpoint = !is_null($orderId) ? 'v2/orders/'.$orderId : 'v2/orders';
            $response = $this->client->get($endpoint, [
                'query' => $params,
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Create an order in BigCommerce.
     *
     * @param array $data
     * @return array|string
     */
    public function createOrder($data)
    {
        try {
            // Send POST request to create an order
            $response = $this->client->post('v2/orders', [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Update an order in BigCommerce.
     *
     * @param array $data
     * @return array|string
     */
    public function updateOrder($data, $id)
    {
        try {
            // Send PUT request to update an order
            $response = $this->client->put('v2/orders/'.$id, [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get All products from BigCommerce.
     *
     * @param array $data
     * @return array|string
     */
    public function getProducts($data)
    {
        try {
            // Send PUT request to update an order
            $response = $this->client->get('v3/catalog/products', []);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Update Product of BigCommerce.
     *
     * @param array $data
     * @return array|string
     */
    public function updateProduct($data)
    {
        try {
            // Send PUT request to update an order
            $response = $this->client->get('v3/catalog/products/'.$data['id'], [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    public function getProduct($url)
    {
         // Initialize the Guzzle HTTP client
         $getProduct = new Client([
            'base_uri' => $url,
            'headers' => [
                'X-Auth-Token' => env('BIGCOMMERCE_ACCESS_TOKEN'),
                'Accept' => 'application/json',
            ],
        ]);
        $response = $getProduct->get('', []);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create a webhook in BigCommerce.
     *
     * @param array $data
     * @return array|string
     */
    public function createWebhook($data)
    {
        try {
            // Send POST request to create a webhook
            $response = $this->client->post('v3/hooks', [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

}
