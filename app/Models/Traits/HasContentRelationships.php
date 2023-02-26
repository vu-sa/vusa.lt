<?php

namespace App\Models\Traits;

use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;

trait HasContentRelationships
{
    public function outgoingRelationships()
    {
        return $this->morphToMany(Relationship::class, 'relationshipable')->using(Relationshipable::class)->withPivot(['related_model_id', 'relationshipable_id']);
    }

    public function incomingRelationships()
    {
        return $this->morphToMany(Relationship::class, 'relationshipable', null, 'related_model_id')->using(Relationshipable::class)->withPivot(['related_model_id', 'relationshipable_id']);
    }
}
