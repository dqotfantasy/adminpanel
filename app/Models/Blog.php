<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{


    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'description',
        'photo',
        'status'
    ];

    protected $appends = ['image_path'];

    public function getImagePathAttribute()
    {
        return $this->getAttributeValue('photo');

        return url('image') . '/' . $path;
    }
}
