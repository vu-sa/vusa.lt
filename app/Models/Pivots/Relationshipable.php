<?php

namespace App\Models\Pivots;

use App\Models\Relationship;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Relationshipable extends MorphPivot
{
    protected $table = 'relationshipables';

    protected $guarded = [];

    public function relationshipable()
    {
        return $this->morphTo('relationshipable', 'relationshipable_type', 'relationshipable_id');
    }

    // Ryšio gavėjas
    public function related_model() {
        return $this->morphTo('related_model', 'relationshipable_type');
    }

    // Ryšys
    public function relationship() {
        return $this->belongsTo(Relationship::class);
    }

}
