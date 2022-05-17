<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'short_name',
        'birthdate',
        'nationality',
        'batting_style',
        'bowling_style',
        'country',
        'image',
    ];

    protected $appends = ['image_path'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getImagePathAttribute()
    {
        $path = $this->getAttributeValue('image');

        if (isset($path)) {
            return $path;
//            return url('image') . '/' . $path;
        }
        return url('image/players/default.jpg');
    }

    public function setImageAttribute($value)
    {
        if ($value) {
            $this->attributes['image'] = Storage::disk('s3')->url($value);
        }
    }
}
