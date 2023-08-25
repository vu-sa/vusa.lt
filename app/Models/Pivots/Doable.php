<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Doable extends MorphPivot
{
    protected $table = 'doables';

    protected $with = ['doing'];

    public function doing()
    {
        return $this->belongsTo(Doing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
