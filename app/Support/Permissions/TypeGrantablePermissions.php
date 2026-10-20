<?php

namespace App\Support\Permissions;

use App\Models\DutyType;
use App\Models\Role;
use App\Support\MorphMap;

/**
 * What a responsibility given to a whole duty type may not hold. One type grant reaches every duty
 * of the type and its subtypes, so it stays inside each duty's own padalinys and cannot delete for good.
 */
final class TypeGrantablePermissions
{
    /**
     * @param  iterable<string>  $permissions
     * @return list<string>
     */
    public static function violations(iterable $permissions): array
    {
        $violations = [];

        foreach ($permissions as $permission) {
            if (str_ends_with($permission, '.*') || str_contains($permission, '.forceDelete.')) {
                $violations[] = $permission;
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @return list<string>
     */
    public static function violationsOf(Role $role): array
    {
        return self::violations($role->permissions()->pluck('name')->all());
    }

    public static function isGrantedToTypes(Role $role): bool
    {
        return $role->grants()->where('grantee_type', MorphMap::alias(DutyType::class))->exists();
    }
}
