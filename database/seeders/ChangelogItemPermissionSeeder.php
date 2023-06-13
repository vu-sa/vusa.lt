<?php

namespace Database\Seeders;

use App\Enums\CRUDEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;

class ChangelogItemPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = PermissionService::getPermissionsToCreate(['changelogItems']);

        PermissionService::createPermissionsForModel($permissions);
    }
}
