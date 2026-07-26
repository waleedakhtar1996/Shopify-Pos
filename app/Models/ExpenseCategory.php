<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'color'];

    public function shop()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
