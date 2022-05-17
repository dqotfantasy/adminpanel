<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FantasyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'fantasy_point_category_id',
        'type',
        'name',
        'code',
        'point',
        'postfix',
        'note',
    ];
}
