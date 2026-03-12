<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

// * A RoleType is not a type, which role can have, but a role, which (duty) type will also have.
/**
 * @property int $id
 * @property string $role_id
 * @property int $type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read Type $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleType query()
 *
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
