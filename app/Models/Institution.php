<?php

namespace App\Models;

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
    use HasFactory, HasRelationships, HasUlids, SoftDeletes, LogsActivity, Searchable;

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
        return $this->morphMany(SharepointDocument::class, 'documentable');
    }

    public function givenRelationships()
    {
        return $this->morphToMany(Relationship::class, 'relationshipable')->withPivot(['related_model_id', 'relationshipable_id']);
    }

    public function givenRelationshipModels()
    {
        // load relationships on pivot
        $relationships = $this->givenRelationships()->get();

        // get related models
        $relationships->map(function ($relationship) {
            // add related model to pivot
            $relationship->pivot->related_model = Institution::find($relationship->pivot->related_model_id);
        });

        return $relationships;
    }

    public function receivedRelationships()
    {
        return $this->morphToMany(Relationship::class, 'relationshipable', null, 'related_model_id')->withPivot(['related_model_id', 'relationshipable_id']);
    }

    public function receivedTypeRelationships() {
        $types = $this->types()->get()->map(function ($type) {
            return $type->receivedRelationshipModels();
        })->flatten();
        
        return $types;
    }

    public function receivedRelationshipModels()
    {
        // load relationships on pivot
        $relationships = $this->receivedRelationships()->get();

        // get related models
        $relationships->map(function ($relationship) {
            // add related model to pivot
            $relationship->pivot->related_model = Institution::find($relationship->pivot->relationshipable_id);
        });

        return $relationships;
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