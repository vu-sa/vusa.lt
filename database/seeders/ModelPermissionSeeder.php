<?php

namespace Database\Seeders;

use App\Actions\Permissions\SyncPermissionCatalog;
use Illuminate\Database\Seeder;

class ModelPermissionSeeder extends Seeder
{
    public function run(): void
    {
        SyncPermissionCatalog::execute();
    }
}
