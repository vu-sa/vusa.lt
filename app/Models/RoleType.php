<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// * A RoleType is not a type, which role can have, but a role, which (duty) type will also have.
/**
 * @property int $id
 * @property string $role_id
 * @property int $type_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Role $role
 * @property-read \App\Models\Type $type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleType query()
 * @mixin \Eloquent
 */
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
