<?php

namespace App\Models\Pivots;

use App\Models\Doing;
use App\Models\User;
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
