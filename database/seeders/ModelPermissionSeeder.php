<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Permission;
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
            
            foreach (CRUDEnum::toLabels() as $crud) {
                foreach (PermissionScopeEnum::toLabels() as $scope) {
                    $permissionsToCreate[] = $pluralizedModel . '.' . $crud . '.' . $scope;
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
