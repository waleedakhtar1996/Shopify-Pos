<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ShopifyProductSyncService;
use Illuminate\Console\Command;

class CreateTestProduct extends Command
{
    protected $signature = 'shopify:create-test-product {shop_domain}';

    protected $description = 'Create a test product locally and push it to Shopify';

    public function handle(ShopifyProductSyncService $syncService)
    {
        $shopDomain = $this->argument('shop_domain');

        $shop = User::where('name', $shopDomain)->first();

        if (!$shop) {
            $this->error("Shop not found: {$shopDomain}");
            return 1;
        }

        $this->info("Creating test product for {$shop->name} ...");

        try {
            $product = $syncService->createAndPushProduct(
                $shop,
                [
                    'title' => 'App Created Product - ' . now()->format('H:i:s'),
                    'body_html' => '<p>This product was created from Adxsway POS and pushed to Shopify.</p>',
                    'vendor' => 'Adxsway',
                    'product_type' => 'Test',
                    'status' => 'active',
                    'tags' => 'app-created, test',
                ],
                [
                    [
                        'sku' => 'APP-TEST-' . rand(1000, 9999),
                        'barcode' => (string) rand(1000000000, 9999999999),
                        'price' => 999.00,
                        'inventory_quantity' => 10,
                    ],
                ],
                [] // no images for this test
            );

            $this->info("Success! Product created locally with ID: {$product->id}");
            $this->info("Shopify Product ID: {$product->shopify_product_id}");
            $this->info("Variant SKU: {$product->variants->first()->sku}");

        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
        }

        return 0;
    }
}