<?php

namespace App\Models;

use App\Events\FileableNameUpdated;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Institution extends Model
{
    use HasFactory, HasSharepointFiles, HasContentRelationships, HasRelationships, HasUlids, SoftDeletes, LogsActivity, Searchable, HasComments;

    protected $guarded = [];

    protected $with = ['types'];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function padalinys() 
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    public function matters()
    {
        return $this->belongsToMany(Matter::class, 'institutions_matters', 'institution_id', 'matter_id');
    }

    public function meetings()
    {
        return $this->belongsToMany(Meeting::class);
    }

    // TODO: two separate functions for latest in past, and earliest in future
    public function lastMeeting() : ?Meeting
    {
        return $this->meetings()->latest()->first();
    }

    public function users() 
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->users());
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...
        // return only title
        $array = [
            'name' => $this->name,
        ];

        return $array;
    }

    protected static function booted()
    {
        static::saved(function (Institution $institution) {
            // check if institution name $institution->getChanges()['name'] has changed
            if (array_key_exists('name', $institution->getChanges())) {
                // dispatch event FileableNameUpdated
                FileableNameUpdated::dispatch($institution);
            }
        });
    }
}
