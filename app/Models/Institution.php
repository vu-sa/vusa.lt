<?php

namespace App\Models;

use App\Models\Traits\HasComments;
use App\Models\Traits\HasContentRelationships;
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
    use HasFactory, HasContentRelationships, HasRelationships, HasUlids, SoftDeletes, LogsActivity, Searchable, HasComments;

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

    public function documents()
    {
        return $this->morphMany(SharepointFile::class, 'documentable');
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
}
