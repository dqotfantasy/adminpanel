<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Squad extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'team_id',
        'player_id',
        'role',
        'substitute',
        'playing11',
        'playing11_point',
        'fantasy_player_rating',
        'last_played',
        'is_active',
        'runs',
        'runs_point',
        'first_inning',
        'second_inning',
        'third_inning',
        'fourth_inning',
        'fours',
        'fours_point',
        'sixes',
        'sixes_point',
        'century_half_century',
        'century_half_century_point',
        'strike_rate',
        'strike_rate_point',
        'duck',
        'duck_point',
        'wicket',
        'wicket_point',
        'maiden_over',
        'maiden_over_point',
        'economy_rate',
        'economy_rate_point',
        'catch',
        'catch_point',
        'runoutstumping',
        'runoutstumping_point',
        'bonus_point',
        'total_points',
        'in_dream_team',
        'series_point',
        'first_inning',
        'second_inning',
        'third_inning',
        'fourth_inning'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'substitute' => 'boolean',
        'playing11' => 'boolean',
        'last_played' => 'boolean',
        'playing11_point' => 'float',
        'runs_point' => 'float',
        'fours_point' => 'float',
        'sixes_point' => 'float',
        'century_half_century' => 'float',
        'century_half_century_point' => 'float',
        'strike_rate_point' => 'float',
        'duck' => 'boolean',
        'duck_point' => 'float',
        'wicket_point' => 'float',
        'maiden_over_point' => 'float',
        'economy_rate' => 'float',
        'economy_rate_point' => 'float',
        'catch_point' => 'float',
        'runoutstumping_point' => 'float',
        'bonus_point' => 'float',
        'total_points' => 'float',
        'in_dream_team' => 'boolean',
    ];

    protected $appends = ['image_path'];

    public function getImagePathAttribute()
    {
        $path = $this->getAttributeValue('image');

        if (isset($path)) {
            if (Str::startsWith($path, 'players/')) {
                return url('image') . '/' . $path;
            }

            return $path;
        }
        return url('image/players/default.png');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

}
