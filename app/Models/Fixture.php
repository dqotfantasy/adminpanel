<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Fixture extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'competition_id',
        'competition_name',
        'season',
        'verified',
        'pre_squad',
        'teama',
        'teama_id',
        'teama_image',
        'teama_score',
        'teama_short_name',
        'inning_number',
        'total_innings',
        'payment_data',
        'payment_data_all',

        'teamb',
        'teamb_id',
        'teamb_image',
        'teamb_score',
        'teamb_short_name',

        'format',
        'format_str',
        'starting_at',
        'ending_at',

        'is_active',
        'lineup_announced',
        'status',
        'status_note',
        'last_squad_update',
        'mega_value',
        'allow_prize_distribution',
        'cancel_allow'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lineup_announced' => 'boolean',
        'verified' => 'boolean',
        'pre_squad' => 'boolean',
        'allow_prize_distribution' => 'boolean',
        'cancel_allow'=>'boolean'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->getAttributeValue('teama') . ' VS ' . $this->getAttributeValue('teamb');
    }

    public function squads()
    {
        return $this->hasMany(Squad::class);
    }

    public function user_teams()
    {
        return $this->hasMany(UserTeam::class);
    }

    public function user_contests()
    {
        return $this->hasManyThrough(UserContest::class, Contest::class);
    }

    public function user_contests_direct()
    {
        return $this->hasMany(UserContest::class);
    }

    public function user_contests_with_where()
    {
        $botIds = User::query()
            ->where('is_sys_user', 1)
            ->pluck('id');
        return $this->hasManyThrough(UserContest::class, Contest::class)->where('status','COMPLETED')->whereNotIn('user_id', $botIds);
    }

    public function all_user_contests_with_where()
    {
        return $this->hasManyThrough(UserContest::class, Contest::class)->where('status','COMPLETED');
    }

    public function contests()
    {
        return $this->hasMany(Contest::class);
    }

    public function private_contests()
    {
        return $this->hasMany(PrivateContest::class);
    }
}
