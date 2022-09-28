<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoingType extends Model
{
    use HasFactory;

    public function doings()
    {
        return $this->hasMany(Doing::class);
    }
}
