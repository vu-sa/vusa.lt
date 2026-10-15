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
 * `categories.*` outlived its model: there is no Category and nothing checks the permissions, and
 * ModelPermissionSeeder never prunes resources that left ModelEnum.
 */
return new class extends Migration
{
    /** Who held them on 2026-09-28, so down() can put them back. */
    private const array HOLDERS = [
        'Pilnas administratorius' => ['categories.create.*', 'categories.read.*', 'categories.update.*', 'categories.delete.*', 'categories.forceDelete.*'],
        'Revizijos komisijos narys' => ['categories.create.*', 'categories.read.*', 'categories.update.*'],
    ];

    public function up(): void
    {
        $permissions = Permission::query()->where('name', 'like', 'categories.%')->get();
        $roles = Role::query()->whereHas('permissions', fn ($query) => $query->where('name', 'like', 'categories.%'))->get();

        $permissions->each->delete();

        $this->forgetAccessCaches($roles);
    }

    public function down(): void
    {
        foreach (self::HOLDERS['Pilnas administratorius'] as $permission) {
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
