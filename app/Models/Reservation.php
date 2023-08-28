<?php

namespace App\Models;

use App\Models\Pivots\ReservationResource;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasTasks;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Reservation extends Model
{
    use HasFactory, HasComments, HasTasks, HasRelationships, HasUlids, LogsActivity, Searchable, SoftDeletes;

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
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function resources()
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

    public function padaliniai()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->padaliniai());
    }
}
