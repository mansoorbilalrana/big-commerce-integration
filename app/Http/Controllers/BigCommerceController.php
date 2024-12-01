<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Library\BigCommerce;

class BigCommerceController extends Controller
{
    protected $bigCommerce;

    public function __construct()
    {
        $this->bigCommerce = new BigCommerce();
    }

    /**
     * Get orders from BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrders(Request $request)
    {
        $params = $request->all();
        $orders = $this->bigCommerce->getOrders($params);
        return response()->json($orders);
    }

    /**
     * Create an order in BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        $data = $request->all();
        $order = $this->bigCommerce->createOrder($data);
        return response()->json($order);
    }

    /**
     * Update an order in BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        $data = $request->input('status_id');
        $updatedOrder = $this->bigCommerce->updateOrder($data, $request->input('id'));
        return response()->json($updatedOrder);
    }

    /**
     * Get all products from BigCommerce.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts()
    {
        $products = $this->bigCommerce->getProducts([]);
        return response()->json($products);
    }

    /**
     * Update a product in BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProduct(Request $request)
    {
        $data = $request->all();
        $updatedProduct = $this->bigCommerce->updateProduct($data);
        return response()->json($updatedProduct);
    }

    /**
     * Get a specific product from BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProduct(Request $request)
    {
        $url = $request->input('url');
        $product = $this->bigCommerce->getProduct($url);
        return response()->json($product);
    }

    /**
     * Create a webhook in BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createWebhook(Request $request)
    {
        $data = $request->all();
        $webhook = $this->bigCommerce->createWebhook($data);
        return response()->json($webhook);
    }

    /**
     * Create a webhook in BigCommerce.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderCallback(Request $request)
    {
        try{
            $callbackResponse = $request->all();
            $orderId = $callbackResponse['data']['id'];
            $getOrder = $this->bigCommerce->getOrders([], $orderId);

            //  Get Product Info
            $productInfo = [];
            if(isset($getOrder['products'])){
                $productInfo = $this->bigCommerce->getProduct($getOrder['products']['url'] );
            }

            if(count($productInfo)> 0) {
                $product = collect($productInfo)->first();
                $sku = $product['sku'];
            }
            // Prepare Payload & Create Order on Merlin side
            $merlinPayload = [
                
            ];


            // Update order status on BigCommerce side
            $updateProductPayload = [
                "status_id" => 9
            ];
            $updateOrder = $this->bigCommerce->updateOrder($updateProductPayload, $orderId);

            return response()->json(['order'=> $getOrder, 'product'=> $productInfo, 'updated_order'=> $updateOrder]);
        }catch (\Exception $e) {
            return response()->json([ 'success' => false,'message' => $e->getMessage(),], 500);
        }
    }
}
