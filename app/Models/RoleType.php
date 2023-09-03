<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// * A RoleType is not a type, which role can have, but a role, which (duty) type will also have.
class RoleType extends Pivot
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
