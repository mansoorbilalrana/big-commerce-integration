<?php

namespace App\Console\Commands;

use App\Http\Controllers\BigCommerceController;
use DB;
use Illuminate\Console\Command;

class UpdateBcImportProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-bc-import-product';

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
        // Set up batch size
        $batchSize = 10;

        $bigCommerceController = new BigCommerceController();
        $bigCommerceController->addRequestLogs('job/update-bc-import-product', NULL);

        // To update inventory and price for different sets of products, send separate requests for each set.
        DB::table('import_products')
            ->select('id', 'sku_id', 'product_id', 'quantity','price','type' )
            ->whereNotNull('product_id')
            ->whereNotNull('quantity')
            ->where('type','quantity')
             ->orderBy('id')
            ->chunk(100, function ($products) use ($batchSize) {
                // Divide the chunk into batches of 10
                $batches = array_chunk($products->toArray(), $batchSize);

                foreach ($batches as $batch) {
                    $this->bigCommerceProductUpdate($batch);
                }
            });
        DB::table('import_products')
            ->select('id', 'sku_id', 'product_id', 'quantity','price','type' )
            ->whereNotNull('product_id')
            ->whereNotNull('price')
            ->where('type','price')
             ->orderBy('id')
            ->chunk(100, function ($products) use ($batchSize) {
                // Divide the chunk into batches of 10
                $batches = array_chunk($products->toArray(), $batchSize);

                foreach ($batches as $batch) {
                    $this->bigCommerceProductUpdate($batch);
                }
            });
        return $this->comment('Job executed successfully.');
    }

    protected function bigCommerceProductUpdate(array $batch) {
      // Prepare the payload for the BigCommerce API
        $payload = [];

        foreach ($batch as $product) {
            if($product->type == 'quantity') {
                $payload[] = [
                    'id' => $product->product_id, // BigCommerce product ID
                    'inventory_level' => $product->quantity, // Product inventory level to update
                ];
            }else if($product->type == 'price'){
                $payload[] = [
                    'id' => $product->product_id, // BigCommerce product ID
                    'price' => $product->price, // Product inventory level to update
                ];
            }

        }
        $bigCommerce = new \App\Library\BigCommerce();
        $updateProducts = $bigCommerce->updateProduct($payload);
        return true;
    }
}
