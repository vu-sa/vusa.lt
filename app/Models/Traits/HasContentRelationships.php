<?php

namespace App\Models\Traits;

use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasContentRelationships
{
    /** @return MorphToMany<Relationship, $this, Relationshipable, 'pivot'> */
    public function outgoingRelationships(): MorphToMany
    {
        return $this->morphToMany(Relationship::class, 'relationshipable')->using(Relationshipable::class)->withPivot(['related_model_id', 'relationshipable_id', 'scope', 'bidirectional']);
    }

    /** @return MorphToMany<Relationship, $this, Relationshipable, 'pivot'> */
    public function incomingRelationships(): MorphToMany
    {
        return $this->morphToMany(Relationship::class, 'relationshipable', null, 'related_model_id')->using(Relationshipable::class)->withPivot(['related_model_id', 'relationshipable_id', 'scope', 'bidirectional']);
    }
}
