<?php

namespace App\Models;

use App\Models\Pivots\ReservationResource;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasTasks;
use App\States\ReservationResource\Returned;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property string|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read mixed $is_completed
 * @property-read ReservationResource|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 *
 * @method static \Database\Factories\ReservationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Reservation extends Model
{
    use HasComments, HasFactory, HasRelationships, HasTasks, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class)->using(ReservationResource::class)
            ->withPivot(['id', 'start_time', 'end_time', 'quantity', 'state', 'returned_at'])
            ->withTimestamps();
    }

    // TODO: maybe users can have roles inside the reservation (they already have a pivot table)
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->tenants());
    }

    public function getIsCompletedAttribute()
    {
        return $this->resources->every(fn ($resource) => $resource->pivot->state::class === Returned::class);
    }
}
