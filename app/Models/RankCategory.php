<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RankCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'winner'
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($rankCategory) {
            $rankCategory->prizeBreakup()->delete();
        });
    }

    public function prizeBreakup(): HasMany
    {
        return $this->hasMany(Rank::class);
    }
}
