<?php

namespace App\Services;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Permission;

class PermissionService
{
    public static function getPermissionsToCreate(array $pluralizedModels)
    {
        $permissionsToCreate = [];

        foreach ($pluralizedModels as $pluralizedModel) {
            foreach (CRUDEnum::toLabels() as $crud) {
                foreach (PermissionScopeEnum::toLabels() as $scope) {
                    $permissionsToCreate[] = $pluralizedModel . '.' . $crud . '.' . $scope;
                }
            }
        }

        return $permissionsToCreate;
    }

    public static function createPermissionsForModel(array $permissionsToCreate)
    {
        foreach ($permissionsToCreate as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
