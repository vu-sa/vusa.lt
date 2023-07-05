<?php

namespace Database\Seeders;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
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

        foreach (ModelEnum::toLabels() as $model) {
            $pluralizedModel = Str::plural($model);

            // if model is reservationResource, skip
            if ($pluralizedModel === 'reservationResources') {
                continue;
            }

            foreach (CRUDEnum::toLabels() as $crud) {
                foreach (PermissionScopeEnum::toLabels() as $scope) {
                    $permissionsToCreate[] = $pluralizedModel.'.'.$crud.'.'.$scope;
                }
            }
        }

        $permissionsToCreate = array_map(function ($permission) {
            return [
                'name' => $permission,
                'guard_name' => 'web',
            ];
        }, $permissionsToCreate);

        Permission::create($permissionsToCreate);
    }
}
