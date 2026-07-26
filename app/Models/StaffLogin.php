<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLogin extends Model
{
    use HasFactory;

    protected $fillable = ['shop_id', 'username', 'password', 'display_name', 'role'];

    protected $hidden = ['password'];

    public function shop()
    {
        return $this->belongsTo(User::class, 'shop_id');
    }
}
