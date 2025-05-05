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

class Reservation extends Model
{
    use HasComments, HasFactory, HasRelationships, HasTasks, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_time' => $this->start_time ? $this->start_time->timestamp : null,
            'end_time' => $this->end_time ? $this->end_time->timestamp : null,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
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
