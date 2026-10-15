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
 * "Centrinio biuro išteklių administratorius" had grown into a second full administrator: 56
 * permissions, 48 of them outside resources and reservations. Its only holder gets all of those
 * through "Pilnas administratorius" (the four `contacts.*` are checked nowhere), so trimming the
 * role back to what its name says removes no access.
 */
return new class extends Migration
{
    private const string ROLE = 'Centrinio biuro išteklių administratorius';

    /** What production held on 2026-09-26, so down() can put it back. */
    private const array REMOVED = [
        'calendars.create.*', 'calendars.delete.*', 'calendars.read.*', 'calendars.update.*',
        'contacts.create.*', 'contacts.delete.*', 'contacts.read.*', 'contacts.update.*',
        'documents.create.*', 'documents.delete.*', 'documents.read.*', 'documents.update.*',
        'duties.create.*', 'duties.delete.*', 'duties.read.*', 'duties.update.*',
        'files.create.*', 'files.delete.*', 'files.read.*', 'files.update.*',
        'institutions.create.*', 'institutions.delete.*', 'institutions.read.*', 'institutions.update.*',
        'meetings.create.*', 'meetings.delete.*', 'meetings.read.*', 'meetings.update.*',
        'news.create.*', 'news.delete.*', 'news.read.*', 'news.update.*',
        'pages.create.*', 'pages.delete.*', 'pages.read.*', 'pages.update.*',
        'problems.create.*', 'problems.delete.*', 'problems.read.*', 'problems.update.*',
        'sharepointFiles.create.*', 'sharepointFiles.delete.*', 'sharepointFiles.read.*', 'sharepointFiles.update.*',
        'users.create.*', 'users.delete.*', 'users.read.*', 'users.update.*',
    ];

    public function up(): void
    {
        $role = Role::query()->where('name', self::ROLE)->first();

        if ($role === null) {
            return;
        }

        $role->revokePermissionTo(
            $role->permissions->pluck('name')
                ->reject(fn (string $name) => str_starts_with($name, 'resources.') || str_starts_with($name, 'reservations.'))
                ->all()
        );

        $this->forgetAccessCaches($role);
    }

    public function down(): void
    {
        $role = Role::query()->where('name', self::ROLE)->first();

        if ($role === null) {
            return;
        }

        $role->givePermissionTo(Permission::query()->whereIn('name', self::REMOVED)->pluck('name')->all());

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
