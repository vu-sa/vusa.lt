<?php

namespace App\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Models\Pivots\Relationshipable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory, EagerLoadPivotTrait;

    // Basically they are relationship types, not relationships. But oh well...

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Is it safe?
    public function models($model)
    {
        return $this->morphedByMany($model, 'relationshipable');
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
