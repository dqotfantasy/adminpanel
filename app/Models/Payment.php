<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'transaction_id',
        'description',
        'type',
        'contest_id',
        'private_contest_id',
        'reference_id',
        'coupon_id',
        'extra'
    ];

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = strtoupper($value);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
