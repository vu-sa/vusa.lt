<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Trainingable extends MorphPivot
{
    // NOTE: for some reason, if Searchable trait is used on this model, it will cause an error
    // in the update route. But only if the queue driver is set to sync.
    use HasRelationships;

    protected $table = 'trainingables';

    public function trainingable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'trainingable_id');
    }

    public function duty()
    {
        return $this->belongsTo(Duty::class, 'trainingable_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'trainingable_id');
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class, 'trainingable_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
