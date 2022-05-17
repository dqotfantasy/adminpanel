<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'account_number',
        'branch',
        'ifsc_code',
        'photo',
        'state_id',
        'status'
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
