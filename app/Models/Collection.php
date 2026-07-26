<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shopify_collection_id',
        'title',
        'image',
        'description',
        'type',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Product::class, 'collection', 'title');
    }
}
