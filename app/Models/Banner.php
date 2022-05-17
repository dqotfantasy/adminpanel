<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'link',
        'is_active',
        'type',
        'value'
    ];

    protected $appends = ['image_path'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getImagePathAttribute()
    {
        return $this->getAttributeValue('image');
    }
}
