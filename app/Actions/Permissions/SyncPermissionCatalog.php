<?php

namespace App\Actions\Permissions;

use App\Models\Permission;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes the permissions table match PermissionCatalog. Existing grantable rows are never touched,
 * so their ids and the roles holding them stay; running it twice changes nothing.
 */
class SyncPermissionCatalog
{
    /**
     * @return array{created: list<string>, removed: list<array{name: string, held_by: list<string>}>}
     */
    public static function execute(bool $dryRun = false): array
    {
        $stored = Permission::query()->where('guard_name', 'web')->pluck('name');

        $created = PermissionCatalog::missingIn($stored);
        $removed = array_map(
            fn (string $name): array => ['name' => $name, 'held_by' => self::holdersOf($name)],
            PermissionCatalog::orphansIn($stored),
        );

        if (! $dryRun) {
            DB::transaction(function () use ($created, $removed): void {
                $now = now();

                // One insert: findOrCreate per row reloads every permission each time, which made a fresh seed crawl.
                Permission::query()->insert(array_map(fn (string $name): array => [
                    'id' => (string) Str::ulid(),
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $created));

                $ids = Permission::query()->whereIn('name', array_column($removed, 'name'))->pluck('id');

                DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
                Permission::query()->whereKey($ids)->delete();
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return ['created' => $created, 'removed' => $removed];
    }

    /**
     * Roles and directly-permitted models holding the permission.
     *
     * @return list<string>
     */
    public static function holdersOf(string $permission): array
    {
        $viaRoles = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('permissions.name', $permission)
            ->pluck('roles.name');

        $direct = DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('permissions.name', $permission)
            ->get(['model_has_permissions.model_type', 'model_has_permissions.model_id'])
            ->map(fn (object $row): string => "{$row->model_type}:{$row->model_id}");

        return $viaRoles->merge($direct)->values()->all();
    }
}
