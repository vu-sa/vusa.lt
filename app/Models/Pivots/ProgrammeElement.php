<?php

namespace App\Models\Pivots;

use App\Models\ProgrammeBlock;
use App\Models\ProgrammeDay;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class ProgrammeElement extends MorphPivot
{
    protected $table = 'programme_day_elements';

    protected $guarded = [];

    public function elementable()
    {
        return $this->morphTo();
    }

    public function day()
    {
        return $this->belongsTo(ProgrammeDay::class);
    }

    public function blocks()
    {
        return $this->hasMany(ProgrammeBlock::class);
    }
}
