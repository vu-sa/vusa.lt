<?php

namespace App\Models;

use App\Models\Pivots\Relationshipable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory;

    // Basically they are relationship types, not relationships. But oh well...

    protected $guarded = [];

    protected $appends = ['related_model'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Is it safe?
    public function models($model) {
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

    public function getRelatedModelAttribute()
    {
        // gets related model
    }
}
