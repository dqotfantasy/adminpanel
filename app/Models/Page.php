<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $fillable = [
        'title',
        'slug',
        'content'
    ];
    protected $primaryKey = 'slug';

}
