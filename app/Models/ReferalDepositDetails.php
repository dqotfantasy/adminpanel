<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferalDepositDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'earn_by',
        'deposited_amount',
        'payment_id',
        'referal_level',
        'referal_percentage',
        'amount',
        'is_deposieted',
        'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function earnUser()
    {
        return $this->belongsTo(User::class,'earn_by','id');
    }

}
