<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'shopify_order_id',
        'order_number',
        'financial_status',
        'fulfillment_status',
        'payment_method',
        'subtotal_price',
        'total_tax',
        'total_discounts',
        'total_price',
        'total_refunded',
        'currency',
        'customer_name',
        'customer_email',
        'shipping_address',
        'shopify_created_at',
    ];

    protected $casts = [
        'shopify_created_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
