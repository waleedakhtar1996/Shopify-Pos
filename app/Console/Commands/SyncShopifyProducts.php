<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ShopifyProductSyncService;
use Illuminate\Console\Command;

class SyncShopifyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:sync-products {shop_domain? : The myshopify.com domain of the shop to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from Shopify into the local database';

    public function handle(ShopifyProductSyncService $syncService)
    {
        $shopDomain = $this->argument('shop_domain');

        if ($shopDomain) {
            $shops = User::where('name', $shopDomain)->get();
        } else {
            $shops = User::all();
        }

        if ($shops->isEmpty()) {
            $this->error('No matching shop found.');
            return 1;
        }

        foreach ($shops as $shop) {
            $this->info("Syncing products for: {$shop->name} ...");

            try {
                $count = $syncService->syncAllProducts($shop);
                $this->info("Synced {$count} products for {$shop->name}.");
            } catch (\Exception $e) {
                $this->error("Failed for {$shop->name}: " . $e->getMessage());
            }
        }

        return 0;
    }
}