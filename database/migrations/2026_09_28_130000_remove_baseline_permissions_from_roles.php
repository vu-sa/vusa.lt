<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use App\Support\Permissions\BaselineAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles carried permissions that grant nothing every member does not already have
 * ({@see BaselineAccess}): commenting follows the record (only deleting others' comments is a grant), resources and
 * problems are open to all, and a member reads their own duties. `tasks.delete.own` goes too: it let
 * anyone sharing a duty delete their colleagues' tasks.
 */
return new class extends Migration
{
    /** What production held on 2026-09-28, so down() can put it back. */
    private const array REMOVED = [
        'Centrinio biuro išteklių administratorius' => ['resources.read.*'],
        'Centrinio biuro komunikacijos koordinatorius' => ['problems.read.*'],
        'Centrinio biuro studentų atstovų koordinatorius' => ['comments.create.padalinys', 'comments.delete.padalinys', 'comments.read.padalinys', 'comments.update.padalinys', 'problems.read.*'],
        'Išteklių administratorius' => ['resources.read.*'],
        'Komunikacijos koordinatorius' => ['problems.read.*'],
        'Pilnas administratorius' => ['comments.create.*', 'comments.delete.*', 'comments.forceDelete.*', 'comments.read.*', 'comments.update.*', 'problems.read.*', 'resources.read.*'],
        'PKP administratorius' => ['comments.create.own', 'comments.delete.own', 'comments.read.own', 'comments.update.own', 'problems.read.*', 'resources.read.padalinys'],
        'Problemų redaktorius' => ['problems.read.*'],
        'Revizijos komisijos narys' => ['comments.create.*', 'comments.delete.own', 'comments.read.*', 'comments.update.*', 'problems.read.*', 'resources.read.*', 'tasks.delete.own'],
        'Studentų atstovas' => ['comments.create.padalinys', 'comments.read.own', 'comments.update.own', 'duties.read.own', 'problems.read.*', 'resources.read.padalinys', 'tasks.delete.own'],
        'Studentų atstovų koordinatorius' => ['comments.create.own', 'comments.read.own', 'comments.update.own', 'problems.read.*'],
    ];

    public function up(): void
    {
        foreach (Role::query()->with('permissions')->get() as $role) {
            $baseline = $role->permissions->pluck('name')
                ->filter(fn (string $name): bool => $this->isBaseline($name) || $name === 'tasks.delete.own')
                ->values()
                ->all();

            if ($baseline === []) {
                continue;
            }

            $role->revokePermissionTo($baseline);
            $this->forgetAccessCaches($role);
        }

        // Retired for good: ModelPermissionSeeder no longer creates them, so no role can hold them again.
        Permission::query()->get()
            ->filter(fn (Permission $permission): bool => $this->isBaseline($permission->name))
            ->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (collect(self::REMOVED)->flatten()->unique() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::REMOVED as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            $role->givePermissionTo(Permission::query()->whereIn('name', $permissions)->pluck('name')->all());
            $this->forgetAccessCaches($role);
        }
    }

    private function isBaseline(string $permission): bool
    {
        return Str::is(['comments.create.*', 'comments.read.*', 'comments.update.*', 'comments.delete.own'], $permission)
            || str_starts_with($permission, 'resources.read.')
            || str_starts_with($permission, 'problems.read.')
            || $permission === 'duties.read.own';
    }

    /** Same as RoleTypeObserver: these bypass the observers that usually clear the per-user caches. */
    private function forgetAccessCaches(Role $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role->usersThroughDuties->merge($role->users)->each(function (User $user): void {
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
    }
};
