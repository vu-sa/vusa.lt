<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Typeable extends MorphPivot
{
    use HasFactory;

    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function typeable()
    {
        return $this->morphTo();
    }
}
