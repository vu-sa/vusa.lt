<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GoalInstitutionMatter extends Pivot
{
    protected $table = 'goal_institution_matter';

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
