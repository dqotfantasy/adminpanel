<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateContest extends Model
{
    use HasFactory, Uuids;

    protected $fillable = [
        'user_id',
        'fixture_id',
        'invite_code',
        'contest_name',
        'commission',
        'total_teams',
        'entry_fee',
        'max_team',
        'prize',
        'winner_percentage',
        'is_confirmed',
        'prize_breakup',
        'new_prize_breakup',
        'status'
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'prize_breakup' => 'json',
        'new_prize_breakup' => 'json',
        'commission' => 'float'
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function joined()
    {
        return $this->hasMany(UserPrivateContest::class);
    }
}
