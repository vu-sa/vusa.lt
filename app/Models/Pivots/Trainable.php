<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Trainable extends MorphPivot
{
    // NOTE: for some reason, if Searchable trait is used on this model, it will cause an error
    // in the update route. But only if the queue driver is set to sync.
    use HasRelationships;

    protected $table = 'trainables';

    public function trainable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'trainable_id');
    }

    public function duty()
    {
        return $this->belongsTo(Duty::class, 'trainable_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'trainable_id');
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class, 'trainable_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
