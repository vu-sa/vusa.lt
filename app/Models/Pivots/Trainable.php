<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property string $training_id
 * @property string $trainable_type
 * @property string $trainable_id
 * @property int|null $tenant_id
 * @property int|null $quota
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Duty|null $duty
 * @property-read Institution|null $institution
 * @property-read Membership|null $membership
 * @property-read Tenant|null $tenant
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $trainable
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Trainable query()
 *
 * @mixin \Eloquent
 */
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
