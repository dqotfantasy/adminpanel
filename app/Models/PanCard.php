<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PanCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'pan_number',
        'date_of_birth',
        'is_verified',
        'photo',
        'message'
    ];

    protected $hidden = ['photo'];

    protected $appends = ['image_path'];

    public function getImagePathAttribute()
    {
        $path = $this->getAttributeValue('photo');
        if (isset($path)) {
            return $path;
        }
        return url('image') . '/' . $path;
    }
}
