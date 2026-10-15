<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permanent deletion only exists where there is a trash to empty. Reservations lost soft deletes
 * (2026_09_27_111500) and comments are erased, not trashed (2026_09_28_160000), yet both kept their forceDelete
 * permissions because ModelPermissionSeeder only adds and prunes when it is re-run.
 */
return new class extends Migration
{
    private const array PERMISSIONS = [
        'comments.forceDelete.padalinys',
        'comments.forceDelete.*',
        'reservations.forceDelete.padalinys',
        'reservations.forceDelete.*',
    ];

    /** Who held them on 2026-09-28, so down() can put them back. */
    private const array HOLDERS = [
        'Pilnas administratorius' => ['comments.forceDelete.*', 'reservations.forceDelete.*'],
    ];

    public function up(): void
    {
        $permissions = Permission::query()->whereIn('name', self::PERMISSIONS)->get();

        $roles = Role::query()->whereHas('permissions', fn ($query) => $query->whereIn('name', self::PERMISSIONS))->get();

        $permissions->each->delete();

        $this->forgetAccessCaches($roles);
    }

    public function down(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = Role::query()->whereIn('name', array_keys(self::HOLDERS))->get();

        foreach ($roles as $role) {
            $role->givePermissionTo(self::HOLDERS[$role->name]);
        }

        $this->forgetAccessCaches($roles);
    }

    /**
     * Same as RoleTypeObserver: these bypass the observers that usually clear the per-user caches.
     *
     * @param  iterable<Role>  $roles
     */
    private function forgetAccessCaches(iterable $roles): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($roles as $role) {
            $role->usersThroughDuties->merge($role->users)->each(function (User $user): void {
                PermissionMapBuilder::forgetCachedMaps($user->id);
                Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
            });
        }
    }
};
