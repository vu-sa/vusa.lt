<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }
        if ($teams && empty($columnNames['team_foreign_key'] ?? null)) {
            throw new \Exception('Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id'); // permission id
            $table->string('name');       // For MySQL 8.0 use string('name', 125);
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // rename roles table to old_roles
        Schema::rename('roles', 'old_roles');

        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id'); // role id
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');       // For MySQL 8.0 use string('name', 125);
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->unsignedBigInteger(PermissionRegistrar::$pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign(PermissionRegistrar::$pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], PermissionRegistrar::$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([PermissionRegistrar::$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }

        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->unsignedBigInteger(PermissionRegistrar::$pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign(PermissionRegistrar::$pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger(PermissionRegistrar::$pivotPermission);
            $table->unsignedBigInteger(PermissionRegistrar::$pivotRole);

            $table->foreign(PermissionRegistrar::$pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign(PermissionRegistrar::$pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([PermissionRegistrar::$pivotPermission, PermissionRegistrar::$pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        Schema::dropIfExists('role_user');

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        $superAdmin = Role::create(['name' => 'Super Admin']);
        $contentEditor = Role::create(['name' => 'Content Editor']);
        $calendarEditor = Role::create(['name' => 'Calendar Editor']);
        $saziningaiAdmin = Role::create(['name' => 'Exam Admin']);
        $user = Role::create(['name' => 'User']);

        $content_create = Permission::create(['name' => 'create unit content']);
        $content_edit = Permission::create(['name' => 'edit unit content']);
        $content_delete = Permission::create(['name' => 'delete unit content']);
        $calendar_create = Permission::create(['name' => 'create unit calendar']);
        $calendar_edit = Permission::create(['name' => 'edit unit calendar']);
        $calendar_delete = Permission::create(['name' => 'delete unit calendar']);
        $users_create = Permission::create(['name' => 'create unit users']);
        $users_edit = Permission::create(['name' => 'edit unit users']);
        $users_delete = Permission::create(['name' => 'delete unit users']);
        $duties_create = Permission::create(['name' => 'create unit duties']);
        $duties_edit = Permission::create(['name' => 'edit unit duties']);
        $duties_delete = Permission::create(['name' => 'delete unit duties']);
        $saziningai_create = Permission::create(['name' => 'create saziningai content']);
        $saziningai_edit = Permission::create(['name' => 'edit saziningai content']);
        $saziningai_delete = Permission::create(['name' => 'delete saziningai content']);

        $contentEditor->syncPermissions($content_create, $content_edit, $content_delete, $calendar_create, $calendar_edit, $calendar_delete, $users_create, $users_edit, $duties_create, $duties_edit);
        $calendarEditor->syncPermissions($calendar_create, $calendar_edit, $calendar_delete);
        $saziningaiAdmin->syncPermissions($saziningai_create, $saziningai_edit, $saziningai_delete);

        $users = User::all();

        foreach ($users as $user) {
            $old_role = DB::table('old_roles')->where('id', $user->role_id)->first();
            if (!is_null($old_role)) {
                switch ($old_role->alias)  {
                    case 'admin':
                        $user->assignRole('Super Admin');
                        break;
                    
                    case 'padaliniai-admin':
                        $user->assignRole('Content Editor');
                        break;

                    case 'kalendorius':
                        $user->assignRole('Calendar Editor');
                        break;
                    
                    case 'saziningai':
                        $user->assignRole('Exam Admin');
                        break;

                    case 'naudotojai':
                        $user->assignRole('User');
                        break;

                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);

        Schema::rename('old_roles', 'roles');
    }
}
