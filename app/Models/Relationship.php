<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory;

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

    public function duty_institutions()
    {
        return $this->morphedByMany(DutyInstitution::class, 'relationshipable');
    }

    public function getRelatedModelAttribute()
    {
        // gets related model
    }
}
