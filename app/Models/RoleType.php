<?php

namespace App\Models;

use App\Events\RoleTypeDeleted;
use App\Events\RoleTypeSaved;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleType extends Pivot
{
    protected $dispatchesEvents = [
        'saved' => RoleTypeSaved::class,
        'deleted' => RoleTypeDeleted::class,
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
