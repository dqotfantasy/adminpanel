<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'season',
        'datestart',
        'dateend',
        'category',
        'match_format',
        'is_active',
        'status',
        'prize_breakup',
        'is_leaderboard'
    ];

    protected $casts = [
        'prize_breakup' => 'json',
        'is_leaderboard' => 'boolean',
    ];

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'competition_id');
    }
}
