<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;


class DutyInstitution extends Model
{
    use HasFactory, HasRelationships;

    protected $table = 'duties_institutions';

    protected $guarded = [];

    protected $with = ['types'];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function duties()
    {
        return $this->hasMany(Duty::class, 'institution_id');
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function padalinys() {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'institution_id');
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
            $relationship->pivot->related_model = DutyInstitution::find($relationship->pivot->related_model_id);
        });

        return $relationships;
    }

    public function receivedRelationships()
    {
        return $this->morphToMany(Relationship::class, 'relationshipable', null, 'related_model_id')->withPivot(['related_model_id', 'relationshipable_id']);
    }

    public function receivedRelationshipModels()
    {
        // load relationships on pivot
        $relationships = $this->receivedRelationships()->get();

        // get related models
        $relationships->map(function ($relationship) {
            // add related model to pivot
            $relationship->pivot->related_model = DutyInstitution::find($relationship->pivot->relationshipable_id);
        });

        return $relationships;
    }
 
}
