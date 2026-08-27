<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'purchase_number', 'supplier_name', 'supplier_contact',
        'purchase_date', 'subtotal', 'discount', 'tax', 'shipping_cost',
        'total', 'payment_status', 'payment_type', 'amount_paid', 'status', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
