<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ShopifyProductSyncService;
use Illuminate\Console\Command;

class SyncShopifyCollections extends Command
{
    protected $signature = 'shopify:sync-collections {shop_domain}';
    protected $description = 'Sync collections from Shopify into local database';

    public function handle(ShopifyProductSyncService $syncService)
    {
        $shop = User::where('name', $this->argument('shop_domain'))->first();

        if (!$shop) {
            $this->error('Shop not found.');
            return 1;
        }

        $count = $syncService->syncCollections($shop);
        $this->info("Synced {$count} collections.");
        return 0;
    }
}
