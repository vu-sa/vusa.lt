<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Duty;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

/**
 * Production had no type → role links, so a duty tagged "Studentų atstovas" did not get the role
 * that goes with it. Linking through Type::roles() fires RoleTypeObserver, which also hands the
 * role to every duty that already carries the type.
 *
 * It also splits page and problem editing out of "Išteklių administratorius" into their own roles,
 * handed to every current holder so nobody loses access. A fresh database (roles and types come
 * from seeders) has nothing to link or split, so each step skips what is missing.
 */
return new class extends Migration
{
    private const array PROBLEM_EDITOR_PERMISSIONS = [
        'problems.create.padalinys',
        'problems.read.*',
        'problems.update.padalinys',
    ];

    private const array PAGE_EDITOR_PERMISSIONS = [
        'pages.read.padalinys',
        'pages.update.padalinys',
    ];

    private const string RESOURCE_MANAGER = 'Išteklių administratorius';

    public function up(): void
    {
        $problemEditor = null;

        $this->link('studentu-atstovai', Role::query()->where('name', 'Studentų atstovas')->first());

        if (Permission::query()->whereIn('name', self::PROBLEM_EDITOR_PERMISSIONS)->count() === count(self::PROBLEM_EDITOR_PERMISSIONS)) {
            $problemEditor = Role::firstOrCreate(['name' => 'Problemų redaktorius', 'guard_name' => 'web']);
            $problemEditor->syncPermissions(self::PROBLEM_EDITOR_PERMISSIONS);

            $this->link('koordinatoriai', $problemEditor);
        }

        $pageEditor = null;

        if (Permission::query()->whereIn('name', self::PAGE_EDITOR_PERMISSIONS)->count() === count(self::PAGE_EDITOR_PERMISSIONS)) {
            $pageEditor = Role::firstOrCreate(['name' => 'Padalinio puslapių redaktorius', 'guard_name' => 'web']);
            $pageEditor->syncPermissions(self::PAGE_EDITOR_PERMISSIONS);
        }

        $resourceManager = Role::query()->where('name', self::RESOURCE_MANAGER)->first();

        if ($resourceManager !== null) {
            $this->splitOut($resourceManager, $pageEditor, self::PAGE_EDITOR_PERMISSIONS);
            $this->splitOut($resourceManager, $problemEditor, self::PROBLEM_EDITOR_PERMISSIONS);
            $this->forgetAccessCaches($resourceManager);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $resourceManager = Role::query()->where('name', self::RESOURCE_MANAGER)->first();

        if ($resourceManager !== null) {
            $moved = [...self::PAGE_EDITOR_PERMISSIONS, ...self::PROBLEM_EDITOR_PERMISSIONS];
            $resourceManager->givePermissionTo(Permission::query()->whereIn('name', $moved)->pluck('name')->all());
            $this->forgetAccessCaches($resourceManager);
        }

        foreach (['studentu-atstovai' => 'Studentų atstovas', 'koordinatoriai' => 'Problemų redaktorius'] as $slug => $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            // detach() fires RoleTypeObserver::deleted, which takes the role back from the typed duties.
            if ($role !== null) {
                Type::query()->where('slug', $slug)->first()?->roles()->detach($role->id);
            }
        }

        Role::query()->whereIn('name', ['Problemų redaktorius', 'Padalinio puslapių redaktorius'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Only when the resource manager role holds the whole set, so each holder ends up with
     * exactly the access they had — a partial set stays where it is.
     *
     * @param  list<string>  $permissions
     */
    private function splitOut(Role $resourceManager, ?Role $newRole, array $permissions): void
    {
        if ($newRole === null || $resourceManager->permissions()->whereIn('name', $permissions)->count() !== count($permissions)) {
            return;
        }

        $resourceManager->duties->each(fn (Duty $duty) => $duty->assignRole($newRole));
        $resourceManager->users->each(fn (User $user) => $user->assignRole($newRole));
        $resourceManager->revokePermissionTo($permissions);
    }

    /** Same as RoleTypeObserver: role changes made here bypass the observers that usually clear these. */
    private function forgetAccessCaches(Role $role): void
    {
        $role->usersThroughDuties->merge($role->users)->each(function (User $user): void {
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
    }

    private function link(string $typeSlug, ?Role $role): void
    {
        $type = Type::query()->where('slug', $typeSlug)->first();

        if ($type !== null && $role !== null) {
            $type->roles()->syncWithoutDetaching([$role->id]);
        }
    }
};
