<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $studentRepCoordinator = Role::create(['name' => 'Institution Manager']);

        $institution_create = Permission::create(['name' => 'create institution content']);
        $institution_edit = Permission::create(['name' => 'edit institution content']);
        $institution_delete = Permission::create(['name' => 'delete institution content']);

        $studentRepCoordinator->syncPermissions($institution_create, $institution_edit, $institution_delete);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $studentRepCoordinator = Role::where('name', 'Institution Manager')->first();

        $institution_create = Permission::where('name', 'create institution content')->first();
        $institution_edit = Permission::where('name', 'edit institution content')->first();
        $institution_delete = Permission::where('name', 'delete institution content')->first();

        $studentRepCoordinator->revokePermissionTo($institution_create, $institution_edit, $institution_delete);

        $studentRepCoordinator->delete();

        $institution_create->delete();
        $institution_edit->delete();
        $institution_delete->delete();
    }
};
