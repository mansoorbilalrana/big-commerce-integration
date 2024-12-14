<?php

namespace App\Console\Commands;

use App\Http\Controllers\BigCommerceController;
use Illuminate\Console\Command;
use App\Library\Merlin;
use App\Models\Product;
use Illuminate\Support\Facades\DB;


class MerlinProduct extends Command
{
    protected $bigCommerce;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-merlin-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try{
            $bigCommerceController = new BigCommerceController();
            $bigCommerceController->addRequestLogs('job/add-merlin-stock', NULL);

            $getExistingProducts = Product::get();
            $start = 0;
            $results = 100; // Number of results per request
            $allProducts = []; // To store all the products

            // Loop to handle pagination
            while (true) {
                // Generate XML Payload for current start and results
                $payload = $this->generateXmlPayload($start, $results);

                // Get products for this chunk
                $merlinProducts = $this->getMerlinProducts($payload);

                // If no products are returned, break the loop
                if (empty($merlinProducts['items'])) {
                    break;
                }

                // Process products
                foreach ($merlinProducts['items'] as $product) {
                    $prodSku = $product['sku'];
                    $chkProd = $getExistingProducts->where('sku_id', $prodSku)->first();

                    // Check if the product exists in database
                    if (!is_null($chkProd)) {
                        $allProducts[] = [
                            'sku_id' => $chkProd->sku_id,
                            'product_id' => $chkProd->product_id ?? NULL,
                            'quantity' => $product['qty_free'],
                            'created_at' => $chkProd->created_at,
                            'updated_at' => now(),
                        ];
                    } else {
                        // Fetch product from BigCommerce
                        $bigCommerce = new \App\Library\BigCommerce();
                        $bigCommerceProd = $bigCommerce->getProducts(['sku' => $prodSku]);

                        if (is_array($bigCommerceProd['data']) && count($bigCommerceProd['data']) > 0) {
                            $productId = $bigCommerceProd['data'][0]['id'];

                            $allProducts[] = [
                                'sku_id' => $prodSku,
                                'product_id' => $productId,
                                'quantity' => $product['qty_free'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        else if(!is_array($bigCommerceProd['data'])){
                            $bigCommerceController->addRequestLogs('job/get-bc-product', NULL);
                        }
                        // else{
                        //     $allProducts[] = [
                        //         'sku_id' => $prodSku,
                        //         'product_id' => NULL,
                        //         'quantity' => $product['qty_free'],
                        //         'created_at' => now(),
                        //         'updated_at' => now(),
                        //     ];
                        // }
                    }
                }

                $start += $results;

                if (count($allProducts) >= 100) {
                    DB::table('products')->upsert(
                        $allProducts,
                        ['sku_id'],
                        ['product_id', 'quantity', 'created_at', 'updated_at']
                    );

                    $allProducts = [];
                }
            }

            // If there are any remaining products after the loop, upsert them
            if (count($allProducts) > 0) {
                DB::table('products')->upsert(
                    $allProducts,
                    ['sku_id'],
                    ['product_id', 'quantity', 'created_at', 'updated_at']
                );
            }
            return $this->comment('Job executed successfully.');
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function getMerlinProducts($payload) {
        return Merlin::getStock($payload);
    }

    /**
     * Create XML Payload for Set Order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse | mixed
     */
    public function generateXmlPayload($start, $results)
    {
        try {
             // Raw XML as string to send
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                               xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
                               xmlns:ccbs="http://www.example.com/CCBS"> <!-- Valid URI to avoid errors -->
                    <soap:Body>
                        <GetStockList xmlns="CCBS">
                            <datasource>' . htmlspecialchars(env('MERLIN_DATASOURCE')) . '</datasource>
                            <fields></fields>
                            <search></search>
                            <start>' . $start . '</start>
                            <numberresults>' . $results . '</numberresults>
                        </GetStockList>
                    </soap:Body>
                </soap:Envelope>';

            // Return raw XML as string
            return $xmlString;

        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
