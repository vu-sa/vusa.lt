<?php

namespace App\Models\Pivots;

use App\Models\Contact;
use App\Models\Duty;
use App\Models\Traits\HasUnitRelation;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Dutiable extends MorphPivot
{
    // NOTE: for some reason, if Searchable trait is used on this model, it will cause an error
    // in the update route. But only if the queue driver is set to sync.
    use HasRelationships, HasUlids, HasUnitRelation;

    protected $table = 'dutiables';

    protected $guarded = [];

    protected $dispatchesEvents = [
        'saved' => \App\Events\DutiableChanged::class,
        'deleted' => \App\Events\DutiableChanged::class,
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'extra_attributes' => AsArrayObject::class,
    ];

    public function dutiable()
    {
        return $this->morphTo();
    }

    public function duty()
    {
        return $this->belongsTo(Duty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'dutiable_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'dutiable_id');
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->duty(), (new Duty)->tenants());
    }
}
