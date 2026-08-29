<?php

namespace App\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Models\Pivots\Relationshipable;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, Relationshipable> $relationshipables
 * @property-read Collection<int, Type> $types
 *
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Relationship newModelQuery()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Relationship newQuery()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Relationship query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class Relationship extends Model
{
    use EagerLoadPivotTrait, HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Is it safe?
    /**
     * @param  string|null  $model  a morph alias (what the request submits) or a class name
     */
    public function models($model = null)
    {
        if ($model) {
            return $this->morphedByMany(MorphMap::classFor($model) ?? $model, 'relationshipable');
        }

        return $this;
    }

    public function institutions()
    {
        return $this->morphedByMany(Institution::class, 'relationshipable');
    }

    public function relationshipables()
    {
        return $this->hasMany(Relationshipable::class);
    }

    public function types()
    {
        return $this->morphedByMany(Type::class, 'relationshipable');
    }
}
