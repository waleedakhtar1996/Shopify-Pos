<?php
namespace App\Services;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\Collection;
use App\Models\User;

class ShopifyProductSyncService
{
    protected function api(User $shop)
    {
        return $shop->api();
    }

    public function syncAllProducts(User $shop)
    {
        $api = $this->api($shop);
        $synced = 0;
        $params = ['limit' => 250];
        do {
            $response = $api->rest('GET', '/admin/api/2024-04/products.json', $params);
            $products = $response['body']['products'] ?? [];
            foreach ($products as $shopifyProduct) {
                $this->saveProduct($shop, $shopifyProduct);
                $synced++;
            }
            $params = $this->getNextPageParams($response);
        } while ($params !== null);
        return $synced;
    }

    public function syncVariantCosts(User $shop)
    {
        $api = $this->api($shop);
        $updated = 0;

        \App\Models\ProductVariant::whereHas('product', function ($q) use ($shop) {
                $q->where('user_id', $shop->id);
            })
            ->whereNotNull('inventory_item_id')
            ->select('id', 'inventory_item_id')
            ->orderBy('id')
            ->chunk(100, function ($variants) use ($api, &$updated) {
                $ids = $variants->pluck('inventory_item_id')->implode(',');
                $response = $api->rest('GET', '/admin/api/2024-04/inventory_items.json', ['ids' => $ids]);
                $body = is_array($response['body']) ? $response['body'] : json_decode(json_encode($response['body']), true);
                $items = $body['inventory_items'] ?? [];

                $costByInvItemId = [];
                foreach ($items as $item) {
                    $item = is_array($item) ? $item : (array) $item;
                    $costByInvItemId[$item['id']] = $item['cost'] ?? null;
                }

                foreach ($variants as $variant) {
                    if (isset($costByInvItemId[$variant->inventory_item_id])) {
                        $variant->cost = $costByInvItemId[$variant->inventory_item_id];
                        $variant->save();
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    protected function saveProduct(User $shop, $data)
    {
        $data = is_array($data) ? $data : $data->toArray();

        $product = Product::updateOrCreate(
            [
                'user_id' => $shop->id,
                'shopify_product_id' => $data['id'],
            ],
            [
                'title' => $data['title'] ?? '',
                'body_html' => $data['body_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'status' => $data['status'] ?? 'active',
                'tags' => $data['tags'] ?? null,
                'handle' => $data['handle'] ?? null,
                'option1_name' => $data['options'][0]['name'] ?? null,
                'option2_name' => $data['options'][1]['name'] ?? null,
                'option3_name' => $data['options'][2]['name'] ?? null,
                'shopify_synced_at' => now(),
            ]
        );

        foreach ($data['variants'] ?? [] as $variantData) {
            $variantData = is_array($variantData) ? $variantData : $variantData->toArray();
            ProductVariant::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'shopify_variant_id' => $variantData['id'],
                ],
                [
                    'title' => $variantData['title'] ?? null,
                    'sku' => $variantData['sku'] ?? null,
                    'barcode' => $variantData['barcode'] ?? null,
                    'price' => $variantData['price'] ?? 0,
                    'compare_at_price' => $variantData['compare_at_price'] ?? null,
                    'inventory_item_id' => $variantData['inventory_item_id'] ?? null,
                    'inventory_quantity' => $variantData['inventory_quantity'] ?? 0,
                    'weight' => $variantData['weight'] ?? null,
                    'weight_unit' => $variantData['weight_unit'] ?? null,
                    'option1' => $variantData['option1'] ?? null,
                    'option2' => $variantData['option2'] ?? null,
                    'option3' => $variantData['option3'] ?? null,
                ]
            );
        }

        foreach ($data['images'] ?? [] as $imageData) {
            $imageData = is_array($imageData) ? $imageData : $imageData->toArray();
            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'shopify_image_id' => $imageData['id'],
                ],
                [
                    'src' => $imageData['src'] ?? '',
                    'alt' => $imageData['alt'] ?? null,
                    'position' => $imageData['position'] ?? 1,
                ]
            );
        }
        return $product;
    }

    protected function getNextPageParams($response)
    {
        $nextPageInfo = $response['link']['next'] ?? null;
        if (!$nextPageInfo) {
            return null;
        }
        return ['limit' => 250, 'page_info' => $nextPageInfo];
    }

    /**
     * Sync collections (custom + smart) from Shopify into local DB.
     */
    public function syncCollections(User $shop)
    {
        $api = $this->api($shop);
        $count = 0;

        $custom = $api->rest('GET', '/admin/api/2024-04/custom_collections.json', ['limit' => 250]);
        foreach ($custom['body']['custom_collections'] ?? [] as $c) {
            $c = is_array($c) ? $c : $c->toArray();
            $imageUrl = $c['image']['src'] ?? null;
            $collection = Collection::updateOrCreate(
                ['user_id' => $shop->id, 'shopify_collection_id' => $c['id']],
                ['title' => $c['title'], 'type' => 'custom', 'image' => $imageUrl, 'description' => $c['body_html'] ?? null]
            );
            $this->syncCollectionProductCount($shop, $collection);
            $count++;
        }

        $smart = $api->rest('GET', '/admin/api/2024-04/smart_collections.json', ['limit' => 250]);
        foreach ($smart['body']['smart_collections'] ?? [] as $c) {
            $c = is_array($c) ? $c : $c->toArray();
            $imageUrl = $c['image']['src'] ?? null;
            $collection = Collection::updateOrCreate(
                ['user_id' => $shop->id, 'shopify_collection_id' => $c['id']],
                ['title' => $c['title'], 'type' => 'smart', 'image' => $imageUrl, 'description' => $c['body_html'] ?? null]
            );
            $this->syncCollectionProductCount($shop, $collection);
            $count++;
        }

        return $count;
    }

    public function syncOneCollection(User $shop, Collection $collection)
    {
        $this->syncCollectionProductCount($shop, $collection);
    }

    protected function syncCollectionProductCount(User $shop, Collection $collection)
    {
        try {
            $api = $this->api($shop);
            $shopifyProductIds = collect();

            if ($collection->type === 'smart') {
                // Smart collections: collects.json doesn't work, use products.json?collection_id=
                $sinceId = 0;
                do {
                    $response = $api->rest('GET', '/admin/api/2024-04/products.json', [
                        'collection_id' => $collection->shopify_collection_id,
                        'limit' => 250,
                        'since_id' => $sinceId,
                        'fields' => 'id',
                    ]);
                    $rawProducts = $response['body']['products'] ?? [];
                    $products = is_array($rawProducts) ? $rawProducts : (method_exists($rawProducts, 'toArray') ? $rawProducts->toArray() : (array) $rawProducts);
                    $normalized = [];
                    foreach ($products as $p) {
                        $p = is_array($p) ? $p : (method_exists($p, 'toArray') ? $p->toArray() : (array) $p);
                        $normalized[] = $p;
                        $shopifyProductIds->push($p['id'] ?? null);
                    }
                    $lastItem = count($normalized) > 0 ? $normalized[count($normalized) - 1] : null;
                    $sinceId = $lastItem['id'] ?? 0;
                } while (count($products) >= 250);
            } else {
                // Custom collections: paginate through collects.json
                $sinceId = 0;
                do {
                    $response = $api->rest('GET', '/admin/api/2024-04/collects.json', [
                        'collection_id' => $collection->shopify_collection_id,
                        'limit' => 250,
                        'since_id' => $sinceId,
                    ]);
                    $collects = $response['body']['collects'] ?? [];
                    $lastId = 0;
                    foreach ($collects as $c) {
                        $c = is_array($c) ? $c : (array) $c;
                        if (isset($c['container'])) {
                            $c = is_array($c['container']) ? $c['container'] : (array) $c['container'];
                        }
                        $shopifyProductIds->push($c['product_id'] ?? null);
                        $lastId = $c['id'] ?? $lastId;
                    }
                    $sinceId = $lastId;
                } while (count($collects) >= 250);
            }

            $shopifyProductIds = $shopifyProductIds->filter()->unique()->values();

            $collection->products_count_cache = $shopifyProductIds->count();
            $collection->save();

            $localProductIds = Product::where('user_id', $shop->id)
                ->whereIn('shopify_product_id', $shopifyProductIds)
                ->pluck('id');

            $collection->products()->sync($localProductIds);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Collection product count sync failed for ' . $collection->title . ': ' . $e->getMessage());
        }
    }

    public function createAndPushProduct(User $shop, array $productData, array $variantsData = [], array $imageUrls = [], $collectionId = null)
    {
        // Create product WITHOUT images first (faster, less likely to timeout)
        $product = Product::create(array_merge(['user_id' => $shop->id], $productData));

        foreach ($variantsData as $variantData) {
            $product->variants()->create($variantData);
        }

        // Push product + variants to Shopify first
        $this->pushProductToShopify($shop, $product);

        // Now add images ONE BY ONE (separate API calls, more reliable than bulk)
        foreach ($imageUrls as $index => $url) {
            $localImage = $product->images()->create([
                'src' => $url,
                'position' => $index + 1,
            ]);
            $this->pushSingleImage($shop, $product, $localImage);
        }

        // Link to collection if selected
        if ($collectionId) {
            $this->linkProductToCollection($shop, $product, $collectionId);
        }

        return $product->fresh(['variants', 'images']);
    }

    public function pushProductToShopify(User $shop, Product $product)
    {
        $api = $this->api($shop);

        $options = [];
        if ($product->option1_name) $options[] = ['name' => $product->option1_name];
        if ($product->option2_name) $options[] = ['name' => $product->option2_name];
        if ($product->option3_name) $options[] = ['name' => $product->option3_name];

        $payload = [
            'product' => [
                'title' => $product->title,
                'body_html' => $product->body_html,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'status' => $product->status,
                'tags' => $product->tags,
                'options' => $options,
                'variants' => $product->variants->map(function ($v) use ($product) {
                    return [
                        'sku' => $v->sku,
                        'barcode' => $v->barcode,
                        'price' => $v->price,
                        'compare_at_price' => $v->compare_at_price,
                        'inventory_quantity' => $v->inventory_quantity,
                        'inventory_management' => $product->track_quantity ? 'shopify' : null,
                        'inventory_policy' => $product->continue_selling_when_out_of_stock ? 'continue' : 'deny',
                        'weight' => $v->weight,
                        'weight_unit' => $v->weight_unit,
                        'option1' => $v->option1,
                        'option2' => $v->option2,
                        'option3' => $v->option3,
                        'requires_shipping' => $product->is_physical_product,
                    ];
                })->toArray(),
                'metafields_global_title_tag' => $product->meta_title,
                'metafields_global_description_tag' => $product->meta_description,
            ],
        ];

        $response = $api->rest('POST', '/admin/api/2024-04/products.json', $payload);
        $result = $response['body']['product'] ?? null;
        $result = is_array($result) ? $result : ($result ? $result->toArray() : null);

        if (!$result) {
            throw new \Exception('Failed to create product on Shopify.');
        }

        $product->update([
            'shopify_product_id' => $result['id'],
            'handle' => $result['handle'] ?? null,
            'shopify_synced_at' => now(),
        ]);

        $shopifyVariants = $result['variants'] ?? [];
        foreach ($product->variants as $index => $localVariant) {
            if (isset($shopifyVariants[$index])) {
                $sv = is_array($shopifyVariants[$index]) ? $shopifyVariants[$index] : $shopifyVariants[$index]->toArray();
                $localVariant->update(['shopify_variant_id' => $sv['id']]);
            }
        }

        return $product;
    }

    /**
     * Push a single image to Shopify (one request per image, with 1 retry on failure).
     */
    public function pushSingleImage(User $shop, Product $product, ProductImage $localImage, int $attempt = 1)
    {
        $api = $this->api($shop);

        try {
            $localPath = str_replace(config('app.url') . '/storage/', '', $localImage->src);
            $fullPath = storage_path('app/public/' . $localPath);
            $base64 = base64_encode(file_get_contents($fullPath));

            $response = $api->rest('POST', "/admin/api/2024-04/products/{$product->shopify_product_id}/images.json", [
                'image' => ['attachment' => $base64],
            ]);

            \Illuminate\Support\Facades\Log::info('Image push raw response: ' . json_encode($response['body'] ?? null) . ' | src: ' . $localImage->src);
            $result = $response['body']['image'] ?? null;
            $result = is_array($result) ? $result : ($result ? $result->toArray() : null);

            if ($result) {
                $localImage->update(['shopify_image_id' => $result['id']]);
            }

            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Image push failed (attempt {$attempt}): " . $e->getMessage());
            if ($attempt < 2) {
                // retry once
                return $this->pushSingleImage($shop, $product, $localImage, $attempt + 1);
            }
            // give up on this image after retry, but log it
            return null;
        }
    }

    /**
     * Link a product to a collection by Shopify collection ID.
     */
    public function linkProductToCollection(User $shop, Product $product, $collectionId)
    {
        if (!$product->shopify_product_id) {
            return;
        }

        $api = $this->api($shop);
        try {
            $response = $api->rest('POST', '/admin/api/2024-04/collects.json', [
                'collect' => [
                    'product_id' => $product->shopify_product_id,
                    'collection_id' => $collectionId,
                ],
            ]);
            \Illuminate\Support\Facades\Log::info('Collection link response: ' . json_encode($response['body'] ?? null));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Collection link failed: ' . $e->getMessage());
        }
    }
}
