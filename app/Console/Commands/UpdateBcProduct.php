<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class UpdateBcProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-bc-product';

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

        DB::table('products')
            ->select('id', 'sku_id', 'product_id', 'quantity')
             ->orderBy('id')
            ->chunk(100, function ($products) use ($batchSize) {
                // Divide the chunk into batches of 10
                $batches = array_chunk($products->toArray(), $batchSize);

                foreach ($batches as $batch) {
                    $this->bigCommerceProductUpdate($batch);
                }
            });
    }

    protected function bigCommerceProductUpdate(array $batch) {
      // Prepare the payload for the BigCommerce API
        $payload = [];

        foreach ($batch as $product) {
            $payload[] = [
                'id' => $product->product_id, // BigCommerce product ID
                // 'sku' => $product['sku_id'], // Product SKU
                'inventory_level' => $product->quantity, // Product inventory level to update
            ];
        }
        $bigCommerce = new \App\Library\BigCommerce();
        $updateProducts = $bigCommerce->updateProduct($payload);
        return true;
    }
}
