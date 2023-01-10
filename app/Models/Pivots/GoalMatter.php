<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GoalMatter extends Pivot
{
    protected $casts = [
        'extra_attributes' => AsArrayObject::class,
    ];

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }
}
