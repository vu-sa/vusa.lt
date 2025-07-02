<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use App\Models\StudyProgram;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasUnitRelation;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Dutiable extends MorphPivot
{
    // NOTE: for some reason, if Searchable trait is used on this model, it will cause an error
    // in the update route. But only if the queue driver is set to sync.
    use HasFactory, HasRelationships, HasTranslations, HasUlids, HasUnitRelation;

    protected $table = 'dutiables';

    protected $guarded = [];

    protected $with = ['study_program'];

    protected $dispatchesEvents = [
        'saved' => \App\Events\DutiableChanged::class,
        'deleted' => \App\Events\DutiableChanged::class,
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'use_original_duty_name' => 'boolean',
    ];

    public $translatable = ['description'];

    public function dutiable()
    {
        return $this->morphTo();
    }

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function study_program()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'dutiable_id');
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->duty(), (new Duty)->tenants());
    }
}
