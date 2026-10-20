<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Transitional(responsibility-grants): data-only; delete with its test once it has run on production.
 *
 * The settings manager was a role id in settings; it is the `settings.update.*` permission now.
 */
return new class extends Migration
{
    public function up(): void
    {
        $payload = DB::table('settings')->where('group', 'settings')->where('name', 'settings_manager_role_id')->value('payload');
        $roleId = $payload === null ? null : json_decode($payload, true);
        $permissionId = DB::table('permissions')->where('name', 'settings.update.*')->where('guard_name', 'web')->value('id');

        if (! is_string($roleId) || $permissionId === null || ! DB::table('roles')->where('id', $roleId)->exists()) {
            return;
        }

        DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The role keeps the permission; the settings migration restores an empty setting.
    }
};
