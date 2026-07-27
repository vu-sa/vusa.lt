<?php

namespace Database\Seeders;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModelPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionsToCreate = [];
        $permissionsToDelete = [];

        foreach (ModelEnum::toLabels() as $model) {
            $pluralizedModel = Str::plural($model);

            // if model is reservationResource, skip
            if ($pluralizedModel === 'reservationResources') {
                continue;
            }

            // Get allowed scopes for this model
            $allowedScopes = ModelEnum::getAllowedScopes($pluralizedModel);

            foreach (CRUDEnum::toLabels() as $crud) {
                $isForceDelete = $crud === CRUDEnum::FORCE_DELETE()->label;

                // Permanent deletion only exists for soft-deletable models, and is
                // never granted at the "own" scope — it is a tenant/global action.
                if ($isForceDelete && ! ModelEnum::isSoftDeletable($pluralizedModel)) {
                    foreach (['own', 'padalinys', '*'] as $scope) {
                        $permissionsToDelete[] = $pluralizedModel.'.'.$crud.'.'.$scope;
                    }

                    continue;
                }

                // Special case: institutions only allow "own" scope for read operations
                $operationAllowedScopes = $allowedScopes;
                if ($pluralizedModel === 'institutions' && $crud !== 'read') {
                    $operationAllowedScopes = array_diff($allowedScopes, ['own']);
                }

                if ($isForceDelete) {
                    $operationAllowedScopes = array_intersect($operationAllowedScopes, ['padalinys', '*']);
                }

                // Create permissions for allowed scopes
                foreach ($operationAllowedScopes as $scope) {
                    $permissionsToCreate[] = $pluralizedModel.'.'.$crud.'.'.$scope;
                }

                // Identify permissions to delete (scopes not in allowed list for this operation)
                $allPossibleScopes = ['own', 'padalinys', '*'];
                $disallowedScopes = array_diff($allPossibleScopes, $operationAllowedScopes);

                foreach ($disallowedScopes as $scope) {
                    $permissionsToDelete[] = $pluralizedModel.'.'.$crud.'.'.$scope;
                }
            }
        }

        // Delete permissions that are no longer allowed
        if (! empty($permissionsToDelete)) {
            Permission::whereIn('name', $permissionsToDelete)->delete();
        }

        $permissionsToCreate = array_map(function ($permission) {
            return [
                'name' => $permission,
                'guard_name' => 'web',
            ];
        }, $permissionsToCreate);

        Permission::upsert($permissionsToCreate, ['name', 'guard_name']);
    }
}
