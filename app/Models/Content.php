<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $with = ['parts'];

    public function parts()
    {
        return $this->hasMany(ContentPart::class);
    }
}
