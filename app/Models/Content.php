<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $with = ['parts'];

    protected $guarded = [];

    public function parts()
    {
        return $this->hasMany(ContentPart::class)->orderBy('order');
    }
}
