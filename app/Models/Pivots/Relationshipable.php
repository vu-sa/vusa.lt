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
 * @property string $scope
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
    // Scope constants for relationship resolution
    // Note: 'within-type' sibling relationships are computed dynamically
    // based on Type.extra_attributes.enable_sibling_relationships, not stored here
    public const SCOPE_WITHIN_TENANT = 'within-tenant';

    public const SCOPE_CROSS_TENANT = 'cross-tenant';

    protected $table = 'relationshipables';

    protected $guarded = [];

    protected $attributes = [
        'scope' => self::SCOPE_WITHIN_TENANT,
    ];

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
