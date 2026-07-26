<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CollectionController extends Controller
{
    public function index()
    {
        $shop = Auth::user();

        $collections = Collection::where('user_id', $shop->id)
            ->withCount('products')
            ->latest()
            ->get();

        return view('collections.index', compact('collections'));
    }

    public function create()
    {
        return view('collections.create');
    }

    public function store(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('collections', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        try {
            $api = $shop->api();
            $response = $api->rest('POST', '/admin/api/2024-04/custom_collections.json', [
                'custom_collection' => [
                    'title' => $validated['title'],
                    'body_html' => $validated['description'] ?? null,
                ],
            ]);

            $body = $response['body']['custom_collection'] ?? null;
            $body = is_array($body) ? $body : ($body ? $body->toArray() : null);

            if (!$body || !isset($body['id'])) {
                return back()->withInput()->with('error', 'Failed to create collection on Shopify.');
            }

            $collection = Collection::create([
                'user_id' => $shop->id,
                'shopify_collection_id' => $body['id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'image' => $imageUrl,
                'type' => 'custom',
            ]);

            // Push image to Shopify collection if provided
            if ($imageUrl) {
                try {
                    $fullPath = storage_path('app/public/' . str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH)));
                    $base64 = base64_encode(file_get_contents($fullPath));
                    $api->rest('PUT', "/admin/api/2024-04/custom_collections/{$body['id']}.json", [
                        'custom_collection' => [
                            'id' => $body['id'],
                            'image' => ['attachment' => $base64],
                        ],
                    ]);
                } catch (\Exception $e) {
                    Log::error('Collection image push failed: ' . $e->getMessage());
                }
            }

            return redirect()->route('collections.index')->with('success', "Collection '{$collection->title}' created!");

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function edit(Collection $collection)
    {
        $shop = Auth::user();

        if ($collection->user_id !== $shop->id) {
            abort(403);
        }

        return view('collections.edit', compact('collection'));
    }

    public function update(Request $request, Collection $collection)
    {
        $shop = Auth::user();

        if ($collection->user_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ]);

        $imageUrl = $collection->image;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('collections', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        $collection->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image' => $imageUrl,
        ]);

        try {
            $api = $shop->api();
            $payload = [
                'id' => $collection->shopify_collection_id,
                'title' => $collection->title,
                'body_html' => $collection->description,
            ];

            if ($request->hasFile('image')) {
                $fullPath = storage_path('app/public/' . str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH)));
                $payload['image'] = ['attachment' => base64_encode(file_get_contents($fullPath))];
            }

            $api->rest('PUT', "/admin/api/2024-04/custom_collections/{$collection->shopify_collection_id}.json", [
                'custom_collection' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error('Collection update failed: ' . $e->getMessage());
        }

        return redirect()->route('collections.index')->with('success', 'Collection updated!');
    }

    public function destroy(Collection $collection)
    {
        $shop = Auth::user();

        if ($collection->user_id !== $shop->id) {
            abort(403);
        }

        try {
            $api = $shop->api();
            $api->rest('DELETE', "/admin/api/2024-04/custom_collections/{$collection->shopify_collection_id}.json");
        } catch (\Exception $e) {
            Log::error('Collection delete failed: ' . $e->getMessage());
        }

        $collection->delete();

        return redirect()->route('collections.index')->with('success', 'Collection deleted.');
    }
}
