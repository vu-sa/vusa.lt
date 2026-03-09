<?php

namespace App\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Models\Pivots\Relationshipable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Institution> $institutions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Relationshipable> $relationshipables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types
 *
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Relationship newModelQuery()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Relationship newQuery()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Relationship query()
 *
 * @mixin \Eloquent
 */
class Relationship extends Model
{
    use EagerLoadPivotTrait, HasFactory;

    // Basically they are relationship types, not relationships. But oh well...

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Is it safe?
    public function models($model = null)
    {
        if ($model) {
            return $this->morphedByMany($model, 'relationshipable');
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
