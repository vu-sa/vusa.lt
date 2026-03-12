<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Type> $attachable_types
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, Type> $types
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, User> $usersThroughDuties
 *
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 *
 * @mixin \Eloquent
 */
class Role extends SpatieRole
{
    use HasFactory, HasRelationships, HasUlids;

    public function duties()
    {
        return $this->morphedByMany(Duty::class, 'model', 'model_has_roles');
    }

    public function usersThroughDuties()
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->users());
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
