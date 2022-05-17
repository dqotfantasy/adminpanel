<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestCategory extends Model
{
    use HasFactory, Uuids;

    protected $fillable = ['name', 'tagline', 'is_active','sequence_by'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function contests()
    {
        return $this->hasMany(Contest::class);
    }

}
