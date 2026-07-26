<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Collection;
use App\Services\ShopifyProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $shop = Auth::user();

        $query = Product::with(["variants", "images"])
            ->where("user_id", $shop->id);

        if ($request->filled("search")) {
            $query->where("title", "like", "%" . $request->search . "%");
        }

        if ($request->filled("status") && $request->status !== "all") {
            $query->where("status", $request->status);
        }

        $sort = $request->get("sort", "newest");
        switch ($sort) {
            case "title_asc":
                $query->orderBy("title", "asc");
                break;
            case "title_desc":
                $query->orderBy("title", "desc");
                break;
            case "oldest":
                $query->oldest();
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(20)->withQueryString();

        $freqSeconds = $shop->products_sync_frequency * 60;
        $lastSync = $shop->products_last_synced_at;
        $secondsElapsed = $lastSync ? now()->diffInSeconds($lastSync) : $freqSeconds;
        $secondsRemaining = max(0, $freqSeconds - $secondsElapsed);

        return view("products.index", compact("products", "secondsRemaining", "freqSeconds"));
    }

    public function create()
    {
        $shop = Auth::user();
        $collections = Collection::where('user_id', $shop->id)->orderBy('title')->get();

        return view('products.create', compact('collections'));
    }

    public function store(Request $request, ShopifyProductSyncService $syncService)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'status' => 'required|in:active,draft,archived',
            'tags' => 'nullable|string',
            'collection_id' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'track_quantity' => 'nullable|boolean',
            'continue_selling_when_out_of_stock' => 'nullable|boolean',
            'is_physical_product' => 'nullable|boolean',
            'option1_name' => 'nullable|string|max:100',
            'option2_name' => 'nullable|string|max:100',
            'option3_name' => 'nullable|string|max:100',

            'variants' => 'required|array|min:1',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.barcode' => 'nullable|string|max:255',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.compare_at_price' => 'nullable|numeric|min:0',
            'variants.*.inventory_quantity' => 'required|integer|min:0',
            'variants.*.weight' => 'nullable|numeric|min:0',
            'variants.*.option1' => 'nullable|string|max:255',
            'variants.*.option2' => 'nullable|string|max:255',
            'variants.*.option3' => 'nullable|string|max:255',

            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:5120',
        ]);

        try {
            $imageUrls = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file) {
                        $path = $file->store('products', 'public');
                        $imageUrls[] = Storage::disk('public')->url($path);
                    }
                }
            }

            \Illuminate\Support\Facades\Log::info('Form collection_id received: ' . ($validated['collection_id'] ?? 'NULL'));

            $shopifyCollectionId = null;
            if (!empty($validated['collection_id'])) {
                $col = Collection::where('id', $validated['collection_id'])->where('user_id', $shop->id)->first();
                if ($col) {
                    $shopifyCollectionId = $col->shopify_collection_id;
                }
            }

            $product = $syncService->createAndPushProduct(
                $shop,
                [
                    'title' => $validated['title'],
                    'body_html' => $validated['body_html'] ?? null,
                    'vendor' => $validated['vendor'] ?? null,
                    'product_type' => $validated['product_type'] ?? null,
                    'status' => $validated['status'],
                    'tags' => $validated['tags'] ?? null,
                    'meta_title' => $validated['meta_title'] ?? null,
                    'meta_description' => $validated['meta_description'] ?? null,
                    'track_quantity' => $request->boolean('track_quantity', true),
                    'continue_selling_when_out_of_stock' => $request->boolean('continue_selling_when_out_of_stock', false),
                    'is_physical_product' => $request->boolean('is_physical_product', true),
                    'option1_name' => $validated['option1_name'] ?? null,
                    'option2_name' => $validated['option2_name'] ?? null,
                    'option3_name' => $validated['option3_name'] ?? null,
                ],
                $validated['variants'],
                $imageUrls,
                $shopifyCollectionId
            );

            return redirect()->route('products.index')
                ->with('success', "Product '{$product->title}' created and pushed to Shopify!");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $shop = Auth::user();

        if ($product->user_id !== $shop->id) {
            abort(403);
        }

        $product->load(["variants", "images"]);
        $collections = Collection::where("user_id", $shop->id)->orderBy("title")->get();

        return view("products.edit", compact("product", "collections"));
    }

    public function update(Request $request, Product $product, ShopifyProductSyncService $syncService)
    {
        $shop = Auth::user();

        if ($product->user_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            "title" => "required|string|max:255",
            "body_html" => "nullable|string",
            "vendor" => "nullable|string|max:255",
            "product_type" => "nullable|string|max:255",
            "status" => "required|in:active,draft,archived",
            "tags" => "nullable|string",
            "collection_id" => "nullable|integer",
            "meta_title" => "nullable|string|max:255",
            "meta_description" => "nullable|string",
            "track_quantity" => "nullable|boolean",
            "continue_selling_when_out_of_stock" => "nullable|boolean",
            "is_physical_product" => "nullable|boolean",
            "option1_name" => "nullable|string|max:100",
            "option2_name" => "nullable|string|max:100",
            "option3_name" => "nullable|string|max:100",

            "variants" => "required|array|min:1",
            "variants.*.id" => "nullable|integer",
            "variants.*.sku" => "nullable|string|max:255",
            "variants.*.barcode" => "nullable|string|max:255",
            "variants.*.price" => "required|numeric|min:0",
            "variants.*.compare_at_price" => "nullable|numeric|min:0",
            "variants.*.inventory_quantity" => "required|integer|min:0",
            "variants.*.weight" => "nullable|numeric|min:0",
            "variants.*.option1" => "nullable|string|max:255",
            "variants.*.option2" => "nullable|string|max:255",
            "variants.*.option3" => "nullable|string|max:255",

            "images" => "nullable|array",
            "images.*" => "nullable|image|max:5120",
        ]);

        try {
            $product->update([
                "title" => $validated["title"],
                "body_html" => $validated["body_html"] ?? null,
                "vendor" => $validated["vendor"] ?? null,
                "product_type" => $validated["product_type"] ?? null,
                "status" => $validated["status"],
                "tags" => $validated["tags"] ?? null,
                "meta_title" => $validated["meta_title"] ?? null,
                "meta_description" => $validated["meta_description"] ?? null,
                "track_quantity" => $request->boolean("track_quantity", true),
                "continue_selling_when_out_of_stock" => $request->boolean("continue_selling_when_out_of_stock", false),
                "is_physical_product" => $request->boolean("is_physical_product", true),
                "option1_name" => $validated["option1_name"] ?? null,
                "option2_name" => $validated["option2_name"] ?? null,
                "option3_name" => $validated["option3_name"] ?? null,
            ]);

            // Sync variants: update existing, create new, delete removed
            $keepIds = [];
            foreach ($validated["variants"] as $vData) {
                if (!empty($vData["id"])) {
                    $variant = $product->variants()->find($vData["id"]);
                    if ($variant) {
                        $variant->update([
                            "sku" => $vData["sku"] ?? null,
                            "barcode" => $vData["barcode"] ?? null,
                            "price" => $vData["price"],
                            "compare_at_price" => $vData["compare_at_price"] ?? null,
                            "inventory_quantity" => $vData["inventory_quantity"],
                            "weight" => $vData["weight"] ?? null,
                            "option1" => $vData["option1"] ?? null,
                            "option2" => $vData["option2"] ?? null,
                            "option3" => $vData["option3"] ?? null,
                        ]);
                        $keepIds[] = $variant->id;

                        if ($product->shopify_product_id && $variant->shopify_variant_id) {
                            try {
                                $api = $shop->api();
                                $api->rest("PUT", "/admin/api/2024-04/variants/{$variant->shopify_variant_id}.json", [
                                    "variant" => [
                                        "id" => $variant->shopify_variant_id,
                                        "price" => $variant->price,
                                        "compare_at_price" => $variant->compare_at_price,
                                        "sku" => $variant->sku,
                                        "barcode" => $variant->barcode,
                                        "inventory_quantity" => $variant->inventory_quantity,
                                        "weight" => $variant->weight,
                                        "option1" => $variant->option1,
                                        "option2" => $variant->option2,
                                        "option3" => $variant->option3,
                                    ],
                                ]);
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Shopify variant update failed: " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    $newVariant = $product->variants()->create([
                        "sku" => $vData["sku"] ?? null,
                        "barcode" => $vData["barcode"] ?? null,
                        "price" => $vData["price"],
                        "compare_at_price" => $vData["compare_at_price"] ?? null,
                        "inventory_quantity" => $vData["inventory_quantity"],
                        "weight" => $vData["weight"] ?? null,
                        "option1" => $vData["option1"] ?? null,
                        "option2" => $vData["option2"] ?? null,
                        "option3" => $vData["option3"] ?? null,
                    ]);
                    $keepIds[] = $newVariant->id;

                    if ($product->shopify_product_id) {
                        try {
                            $api = $shop->api();
                            $resp = $api->rest("POST", "/admin/api/2024-04/products/{$product->shopify_product_id}/variants.json", [
                                "variant" => [
                                    "price" => $newVariant->price,
                                    "compare_at_price" => $newVariant->compare_at_price,
                                    "sku" => $newVariant->sku,
                                    "barcode" => $newVariant->barcode,
                                    "inventory_quantity" => $newVariant->inventory_quantity,
                                    "weight" => $newVariant->weight,
                                    "option1" => $newVariant->option1,
                                    "option2" => $newVariant->option2,
                                    "option3" => $newVariant->option3,
                                ],
                            ]);
                            $body = $resp["body"]["variant"] ?? null;
                            $body = is_array($body) ? $body : ($body ? $body->toArray() : null);
                            if ($body && isset($body["id"])) {
                                $newVariant->update(["shopify_variant_id" => $body["id"]]);
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Shopify variant create failed: " . $e->getMessage());
                        }
                    }
                }
            }

            // Delete variants removed from the form
            $toDelete = $product->variants()->whereNotIn("id", $keepIds)->get();
            foreach ($toDelete as $delVariant) {
                if ($product->shopify_product_id && $delVariant->shopify_variant_id) {
                    try {
                        $api = $shop->api();
                        $api->rest("DELETE", "/admin/api/2024-04/products/{$product->shopify_product_id}/variants/{$delVariant->shopify_variant_id}.json");
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Shopify variant delete failed: " . $e->getMessage());
                    }
                }
                $delVariant->delete();
            }

            // Push new images (existing images kept as-is)
            if ($request->hasFile("images")) {
                foreach ($request->file("images") as $file) {
                    if ($file) {
                        $path = $file->store("products", "public");
                        $url = \Illuminate\Support\Facades\Storage::disk("public")->url($path);
                        $localImage = $product->images()->create([
                            "src" => $url,
                            "position" => $product->images()->count() + 1,
                        ]);

                        if ($product->shopify_product_id) {
                            $syncService->pushSingleImage($shop, $product, $localImage);
                        }
                    }
                }
            }

            // Update collection link
            if (!empty($validated["collection_id"])) {
                $col = Collection::where("id", $validated["collection_id"])->where("user_id", $shop->id)->first();
                if ($col && $product->shopify_product_id) {
                    $syncService->linkProductToCollection($shop, $product, $col->shopify_collection_id);
                }
            }

            // Push core product fields to Shopify
            if ($product->shopify_product_id) {
                try {
                    $api = $shop->api();
                    $api->rest("PUT", "/admin/api/2024-04/products/{$product->shopify_product_id}.json", [
                        "product" => [
                            "id" => $product->shopify_product_id,
                            "title" => $product->title,
                            "body_html" => $product->body_html,
                            "vendor" => $product->vendor,
                            "product_type" => $product->product_type,
                            "status" => $product->status,
                            "tags" => $product->tags,
                        ],
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Shopify product update failed: " . $e->getMessage());
                }
            }

            return redirect()->route("products.index")->with("success", "Product updated!");

        } catch (\Exception $e) {
            return back()->withInput()->with("error", "Failed: " . $e->getMessage());
        }
    }


    public function destroy(Request $request, Product $product)
    {
        $shop = Auth::user();

        if ($product->user_id !== $shop->id) {
            abort(403);
        }

        if ($product->shopify_product_id) {
            try {
                $api = $shop->api();
                $api->rest('DELETE', "/admin/api/2024-04/products/{$product->shopify_product_id}.json");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Shopify product delete failed: ' . $e->getMessage());
            }
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function syncAjax(Request $request, ShopifyProductSyncService $syncService)
    {
        $shop = Auth::user();

        try {
            $syncService->syncAllProducts($shop);
            $syncService->syncCollections($shop);

            $shop->products_last_synced_at = now();
            $shop->save();

            return response()->json(['success' => true, 'message' => 'Products synced']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sync(Request $request, ShopifyProductSyncService $syncService)
    {
        $shop = Auth::user();

        try {
            $count = $syncService->syncAllProducts($shop);
            return redirect()->route('products.index')
                ->with('success', "Synced {$count} products from Shopify!");
        } catch (\Exception $e) {
            return redirect()->route('products.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function syncCollections(Request $request, ShopifyProductSyncService $syncService)
    {
        $shop = Auth::user();

        try {
            $count = $syncService->syncCollections($shop);
            return redirect()->route('products.create')
                ->with('success', "Synced {$count} collections from Shopify!");
        } catch (\Exception $e) {
            return redirect()->route('products.create')
                ->with('error', 'Collection sync failed: ' . $e->getMessage());
        }
    }
}
