<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPrivateContest extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'private_contest_id ',
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
