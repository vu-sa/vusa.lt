<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // delete all roles and permissions and all related
        DB::table('roles')->delete();
        DB::table('permissions')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();

        // drop foreigns
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropForeign('model_has_permissions_permission_id_foreign');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign('model_has_roles_role_id_foreign');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign('role_has_permissions_role_id_foreign');
        });

        // change all ids to char(26)
        Schema::table('roles', function (Blueprint $table) {
            $table->char('id', 26)->change();
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign('role_has_permissions_permission_id_foreign');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->char('id', 26)->change();
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->char('permission_id', 26)->change();
            $table->char('model_id', 26)->change();
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->char('role_id', 26)->change();
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->char('permission_id', 26)->change();
            $table->char('role_id', 26)->change();
        });

        // add foreigns
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->index('permission_id', 'role_has_permissions_permission_id_foreign');
        });

        // create Super Admin role
        $superAdmin = Role::create([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
