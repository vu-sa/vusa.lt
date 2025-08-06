<?php

namespace App\Models\Pivots;

use App\Models\Relationship;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @property int $id
 * @property int $relationship_id
 * @property string $relationshipable_type
 * @property string $relationshipable_id
 * @property string $related_model_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $related_model
 * @property-read Relationship $relationship
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $relationshipable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Relationshipable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Relationshipable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Relationshipable query()
 *
 * @mixin \Eloquent
 */
class Relationshipable extends MorphPivot
{
    protected $table = 'relationshipables';

    protected $guarded = [];

    public function relationshipable()
    {
        return $this->morphTo('relationshipable', 'relationshipable_type', 'relationshipable_id');
    }

    // Ryšio gavėjas
    public function related_model()
    {
        return $this->morphTo('related_model', 'relationshipable_type');
    }

    // Ryšys
    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }
}
