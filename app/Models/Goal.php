<?php

namespace App\Models;

use App\Models\Pivots\Doable;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTasks;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Goal extends Model
{
    use HasComments, HasFactory, HasSharepointFiles, HasTasks, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
    }

    public function matters()
    {
        return $this->belongsToMany(Matter::class);
    }

    public function doings()
    {
        return $this->morphToMany(Doing::class, 'doables')->using(Doable::class)->withTimestamps();
    }

    public function group()
    {
        return $this->belongsTo(GoalGroup::class);
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class);
    }
}
