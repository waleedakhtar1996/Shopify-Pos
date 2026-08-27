<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shopify_product_id',
        'title',
        'body_html',
        'vendor',
        'product_type',
        'status',
        'tags',
        'handle',
        'meta_title',
        'meta_description',
        'track_quantity',
        'continue_selling_when_out_of_stock',
        'is_physical_product',
        'collection',
        'option1_name',
        'option2_name',
        'option3_name',
        'shopify_synced_at',
    ];

    protected $casts = [
        'shopify_synced_at' => 'datetime',
        'track_quantity' => 'boolean',
        'continue_selling_when_out_of_stock' => 'boolean',
        'is_physical_product' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function collections()
    {
        return $this->belongsToMany(\App\Models\Collection::class, 'collection_product');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
