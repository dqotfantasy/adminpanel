<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        //'rank_category_id',
        'contest_category_id',
        'name',
        'description',
        'total_teams',
        'entry_fee',
        'max_team',
        'prize',
        'winner_percentage',
        'is_confirmed',
        'prize_breakup',
        'auto_add',
        'auto_create_on_full',
        'commission',
        'type',
        'discount',
        'bonus',
        'is_mega_contest',
        'is_dynamic',
        'dynamic_min_team'
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'prize_breakup' => 'json',
        'auto_add' => 'boolean',
        'is_mega_contest' => 'boolean',
        'auto_create_on_full' => 'boolean',
        'commission' => 'float',
        'bonus' => 'float',
    ];

    // public function rank_category()
    // {
    //     return $this->belongsTo(RankCategory::class);
    // }
}
