<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SalesSyncService;
use App\Services\ShopifyProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllSalesData extends Command
{
    protected $signature = 'shopify:auto-sync';
    protected $description = 'Auto-sync orders, customers, and products for all shops based on their individual frequency settings';

    public function handle(SalesSyncService $salesSyncService, ShopifyProductSyncService $productSyncService)
    {
        $shops = User::whereNotNull('password')->get();

        foreach ($shops as $shop) {
            // Orders
            if ($this->isDue($shop->orders_last_synced_at, $shop->orders_sync_frequency)) {
                try {
                    $count = $salesSyncService->syncOrders($shop);
                    $shop->orders_last_synced_at = now();
                    $shop->save();
                    $this->info("Shop {$shop->id}: synced {$count} orders.");
                } catch (\Exception $e) {
                    Log::error("Auto-sync orders failed for shop {$shop->id}: " . $e->getMessage());
                }
            }

            // Customers
            if ($this->isDue($shop->customers_last_synced_at, $shop->customers_sync_frequency)) {
                try {
                    $count = $salesSyncService->syncCustomers($shop);
                    $shop->customers_last_synced_at = now();
                    $shop->save();
                    $this->info("Shop {$shop->id}: synced {$count} customers.");
                } catch (\Exception $e) {
                    Log::error("Auto-sync customers failed for shop {$shop->id}: " . $e->getMessage());
                }
            }

            // Products & Collections
            if ($this->isDue($shop->products_last_synced_at, $shop->products_sync_frequency)) {
                try {
                    $productSyncService->syncAllProducts($shop);
                    $productSyncService->syncCollections($shop);
                    $shop->products_last_synced_at = now();
                    $shop->save();
                    $this->info("Shop {$shop->id}: synced products & collections.");
                } catch (\Exception $e) {
                    Log::error("Auto-sync products failed for shop {$shop->id}: " . $e->getMessage());
                }
            }
        }
    }

    protected function isDue($lastSyncedAt, $frequencyMinutes)
    {
        if (!$lastSyncedAt) {
            return true;
        }
        return now()->diffInMinutes($lastSyncedAt) >= $frequencyMinutes;
    }
}
