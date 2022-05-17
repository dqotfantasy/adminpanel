<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserTeam extends Model
{
    use HasFactory, Uuids;

    protected $fillable = [
        'fixture_id',
        'user_id',
        'name',
        'players',
        'captain_id',
        'vice_captain_id',
        'total_points'
    ];

    protected $casts = [
        'players' => 'json',
        'total_points' => 'float'
    ];

    public function userContests(): HasMany
    {
        return $this->hasMany(UserContest::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
