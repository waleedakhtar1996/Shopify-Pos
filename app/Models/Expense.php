<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'expense_category_id', 'title', 'amount', 'payment_type', 'expense_date', 'notes'];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function shop()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
