<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FantasyPointCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'note',
        'description'
    ];

    public function fantasy_points()
    {
        return $this->hasMany(FantasyPoint::class);
    }

    protected $appends = ['image_path'];

    public function getImagePathAttribute()
    {
        $path = $this->getAttributeValue('image');

        if (isset($path)) {
            return url('image') . '/' . $path;
        }

        return url('image/positions/other.png');
    }
}
