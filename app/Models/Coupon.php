<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'min_amount',
        'max_cashback',
        'cashback_percentage',
        'usage_limit',
        'limit_per_user',
        'expire_at',
        'wallet_type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
