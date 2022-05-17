<?php

namespace App\Models;


use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    use HasFactory, Uuids;

    protected $fillable = [
        'fixture_id',
        'contest_category_id',
        'invite_code',
        'total_teams',
        'entry_fee',
        'max_team',
        'prize',
        'winner_percentage',
        'is_confirmed',
        'prize_breakup',
        'new_prize_breakup',
        'auto_create_on_full',
        'inning_number',
        'is_dynamic',
        'type',
        'discount',
        'is_mega_contest',
        'status',
        'commission',
        'bonus',
        'dynamic_min_team',
        'contest_template_id'
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'auto_create_on_full' => 'boolean',
        'is_mega_contest' => 'boolean',
        'prize_breakup' => 'json',
        'new_prize_breakup' => 'json',
        'commission' => 'float',
        'bonus' => 'float',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function category()
    {
        return $this->belongsTo(ContestCategory::class, 'contest_category_id');
    }

    public function joined()
    {
        return $this->hasMany(UserContest::class);
    }
}
