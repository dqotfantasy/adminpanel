<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'rank_category_id',
        'rank',
        'from',
        'to',
        'percentage'
    ];

    public function rank_category()
    {
        return $this->belongsTo(RankCategory::class);
    }
}
