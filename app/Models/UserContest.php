<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contest_id',
        'user_team_id',
        'rank',
        'prize',
        'payment_data'
    ];

    protected $casts = [
        'payment_data' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_teams()
    {
        return $this->belongsTo(UserTeam::class, 'user_team_id');
    }
}
