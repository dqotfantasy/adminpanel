<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tds extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'user_id',
        'amount',
        'is_settled',
        'note'
    ];

    protected $casts = [
        'is_settled' => 'boolean',
        'amount' => 'float',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
