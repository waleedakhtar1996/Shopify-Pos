<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'shopify_image_id',
        'src',
        'alt',
        'position',
    ];

    // Image belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}