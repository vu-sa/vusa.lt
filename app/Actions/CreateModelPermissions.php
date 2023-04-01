<?php

namespace App\Actions;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Permission;
use Illuminate\Support\Str;

class CreateModelPermissions
{
    public static function execute(array $modelLabels)
    {
        // foreach (ModelEnum::toLabels() as $model) {
        foreach ($modelLabels as $model) {
            $pluralizedModel = Str::plural($model);

            foreach (CRUDEnum::toLabels() as $crud) {
                foreach (PermissionScopeEnum::toLabels() as $scope) {
                    $permissionsToCreate[] = $pluralizedModel.'.'.$crud.'.'.$scope;
                }
            }
        }

        foreach ($permissionsToCreate as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
