<?php

namespace App\Http\Controllers;

use App\Library\Merlin;
use App\Models\Order;
use Carbon\Carbon;
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
            // Save Order Details
            $createOrder = Order::create([
                "order_id" => $orderId,
                "status" => $getOrder['status'],
                "status_id" => $getOrder['status_id'],
                "big_commerce_response" => json_encode($getOrder),
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now(),
            ]);
            //  Get Product Info
            $productInfo = [];
            if(isset($getOrder['products'])){
                $productInfo = $this->bigCommerce->getProduct($getOrder['products']['url'] );
            }

            if(count($productInfo)> 0) {
                $products = collect($productInfo);
                $orderItems = [];
                foreach($products as $product) {
                    $item = new \stdClass();
                    $item->part = $product['sku'];
                    $item->depot = 'DONC';
                    $item->quantity = $product['quantity'];
                    $item->price = $product['total_inc_tax'];
                    array_push($orderItems, $item);
                }
            }
            // Prepare Payload & Create Order on Merlin side
            $merlinJsonPayload = $this->getMerlinJsonPayload($orderItems, $getOrder);
            $merlinPayload = $this->generateXmlPayload($merlinJsonPayload);

            $merlinResponse = Merlin::setOrder($merlinPayload);
            return $merlinResponse;
            // Update order status on BigCommerce side
            $updateProductPayload = [
                "status_id" => 9
            ];
            $updateOrder = $this->bigCommerce->updateOrder($updateProductPayload, $orderId);
            Order::where('order_id', $orderId)->update([
                "status" => $updateOrder->status,
                "status_id" => $updateOrder->status_id,
            ]);
            return response()->json(['order'=> $getOrder, 'products'=> $productInfo, 'updated_order'=> $updateOrder]);
        }catch (\Exception $e) {
            return response()->json([ 'success' => false,'message' => $e->getMessage(),], 500);
        }
    }


    public function getMerlinJsonPayload($orderItems, $getOrder) {
        try{
            $merlinJsonPayload = [
                "items" => $orderItems,
                "company" => 1,
                "depot" => 'DONC',
                "inv_account" => '1ABP01',
                "inv_name" => $getOrder['billing_address']['first_name'].' '.$getOrder['billing_address']['last_name'],
                "inv_add1" => $getOrder['billing_address']['street_1'],
                "inv_add2" => $getOrder['billing_address']['street_2'],
                "inv_city" => $getOrder['billing_address']['city'],
                "inv_county" => $getOrder['billing_address']['country_iso2'],
                "inv_country" => $getOrder['billing_address']['country'],
                "inv_postcode" => $getOrder['billing_address']['zip'],
                "del_name" => $getOrder['billing_address']['first_name'].' '.$getOrder['billing_address']['last_name'],
                "del_add1" => $getOrder['billing_address']['street_1'],
                "del_add2" => $getOrder['billing_address']['street_2'],
                "del_city" => $getOrder['billing_address']['city'],
                "del_county" => $getOrder['billing_address']['country_iso2'],
                "del_country" => $getOrder['billing_address']['country'],
                "del_postcode" => $getOrder['billing_address']['zip'],
                //
                "due_date" => $getOrder['date_created'],
                "ref" => $getOrder['staff_notes'],
                "carriage" => $getOrder['shipping_cost_inc_tax'],
                "webref1" => "",
                "webref2" => "",
                "contactname" => $getOrder['billing_address']['first_name'].' '.$getOrder['billing_address']['last_name'],
            ];
            return $merlinJsonPayload;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    /**
     * Create XML Payload for Set Order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse | mixed
     */
    public function generateXmlPayload($data)
    {
        try {
            // Sales Order XML
            $xml = new \SimpleXMLElement('<salesorder/>');

            // Add fields dynamically to the sales order
            $xml->addChild('company', $data['company']);
            $xml->addChild('depot', $data['depot']);
            $xml->addChild('inv_account', $data['inv_account']);
            $xml->addChild('inv_name', $data['inv_name']);
            $xml->addChild('inv_add1', $data['inv_add1']);
            $xml->addChild('inv_add2', $data['inv_add2']);
            $xml->addChild('inv_city', $data['inv_city']);
            $xml->addChild('inv_county', $data['inv_county']);
            $xml->addChild('inv_country', $data['inv_country']);
            $xml->addChild('inv_postcode', $data['inv_postcode']);
            $xml->addChild('del_name', $data['del_name']);
            $xml->addChild('del_add1', $data['del_add1']);
            $xml->addChild('del_add2', $data['del_add2']);
            $xml->addChild('del_city', $data['del_city']);
            $xml->addChild('del_county', $data['del_county']);
            $xml->addChild('del_country', $data['del_country']);
            $xml->addChild('del_postcode', $data['del_postcode']);
            $xml->addChild('due_date', $data['due_date']);
            $xml->addChild('ref', $data['ref']);
            $xml->addChild('carriage', $data['carriage']);
            $xml->addChild('webref1', $data['webref1']);
            $xml->addChild('webref2', $data['webref2']);
            $xml->addChild('contactname', $data['contactname']);

            // Add the items, if available
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $itemXml = $xml->addChild('item');
                    $itemXml->addChild('part', $item->part);
                    $itemXml->addChild('depot', $item->depot);
                    $itemXml->addChild('quantity', $item->quantity);
                    $itemXml->addChild('price', $item->price);
                }
            }

            // Create SOAP envelope
            $soapEnvelope = new \SimpleXMLElement('<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
            $soapBody = $soapEnvelope->addChild('soap:Body');
            $setOrder = $soapBody->addChild('SetOrder', null, 'CCBS');

            // Add the 'datasource' element to the SetOrder
            $setOrder->addChild('datasource', env('MERLIN_DATASOURCE'));

            // Add the 'xml' field and wrap the sales order XML in CDATA
            $xmlNode = $setOrder->addChild('xml');
            $xmlNode[0] = $xml->asXML();

            // Manually wrap the XML content inside CDATA
            $xmlString = $soapEnvelope->asXML();
            $xmlString = str_replace('<xml>' . $xmlNode[0] . '</xml>', '<xml><![CDATA[' . $xmlNode[0] . ']]></xml>', $xmlString);

            // Return the complete XML string
            return $xmlString;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

}
