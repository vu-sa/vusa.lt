<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

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
