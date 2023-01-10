<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Dutiable extends MorphPivot
{
    protected $table = 'dutiables';

    protected $with = ['duty'];

    protected $casts = [
        'extra_attributes' => AsArrayObject::class,
    ];

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
