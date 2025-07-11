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
                // Create permissions for allowed scopes
                foreach ($allowedScopes as $scope) {
                    $permissionsToCreate[] = $pluralizedModel.'.'.$crud.'.'.$scope;
                }

                // Identify permissions to delete (scopes not in allowed list)
                $allPossibleScopes = ['own', 'padalinys', '*'];
                $disallowedScopes = array_diff($allPossibleScopes, $allowedScopes);

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
