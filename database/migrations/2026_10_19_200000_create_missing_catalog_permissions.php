<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** What `permissions:audit` found missing on production; a literal list so its meaning cannot drift. */
    private const array PERMISSIONS = [
        'eventTypes.create.*',
        'eventTypes.read.*',
        'eventTypes.update.*',
        'eventTypes.delete.*',
        'eventTypes.forceDelete.*',
        'settings.update.*',
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', self::PERMISSIONS)
            ->whereNotIn('id', DB::table('role_has_permissions')->select('permission_id'))
            ->whereNotIn('id', DB::table('model_has_permissions')->select('permission_id'))
            ->get()
            ->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
