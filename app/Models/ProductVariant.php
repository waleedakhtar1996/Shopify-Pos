<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'shopify_variant_id',
        'title',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'inventory_quantity',
        'weight',
        'weight_unit',
        'option1',
        'option2',
        'option3',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    // Variant belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}