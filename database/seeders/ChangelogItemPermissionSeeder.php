<?php

namespace Database\Seeders;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChangelogItemPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pluralizedModel = 'changelogItems';
        $permissionsToCreate = [];
        
        foreach (CRUDEnum::toLabels() as $crud) {
            foreach (PermissionScopeEnum::toLabels() as $scope) {
                $permissionsToCreate[] = $pluralizedModel . '.' . $crud . '.' . $scope;
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
