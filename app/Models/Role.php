<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Role extends SpatieRole
{
    use HasFactory, HasRelationships, HasUlids;

    public function duties()
    {
        return $this->morphedByMany(Duty::class, 'model', 'model_has_roles');
    }

    public function usersThroughDuties()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty())->users());
    }

    // It describes the types that this role can attach to other objects.
    public function attachable_types()
    {
        return $this->belongsToMany(Type::class, 'role_can_attach_types');
    }

    // It describes the types of duties that grant users this role.
    public function types()
    {
        return $this->belongsToMany(Type::class);
    }
}
