<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Database\Seeder;

class ModelPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::whereIn('name', PermissionCatalog::disallowedNames())->delete();

        Permission::upsert(
            array_map(fn (string $name): array => ['name' => $name, 'guard_name' => 'web'], PermissionCatalog::names()),
            ['name', 'guard_name'],
        );
    }
}
