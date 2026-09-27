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
 * "Studentų atstovas" could record a meeting for any institution of its padalinys, then could not
 * even open it (its read and update are `.own`). It now records meetings for its own institutions only.
 */
return new class extends Migration
{
    private const string ROLE = 'Studentų atstovas';

    private const string PADALINYS = 'meetings.create.padalinys';

    private const string OWN = 'meetings.create.own';

    public function up(): void
    {
        $this->swap(self::PADALINYS, self::OWN);
    }

    public function down(): void
    {
        $this->swap(self::OWN, self::PADALINYS);
    }

    private function swap(string $from, string $to): void
    {
        $role = Role::query()->where('name', self::ROLE)->first();

        if ($role === null || ! $role->permissions->contains('name', $from)) {
            return;
        }

        Permission::findOrCreate($to, 'web');
        $role->revokePermissionTo($from);
        $role->givePermissionTo($to);

        $this->forgetAccessCaches($role);
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
