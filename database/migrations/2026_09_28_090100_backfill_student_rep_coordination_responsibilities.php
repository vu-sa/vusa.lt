<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Duty;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Coordinators used to be whoever held the role picked in Atstovavimo nustatymai. Each duty holding
 * it now coordinates its own padalinys as a duty responsibility, and the role keeps the activity
 * report administration it used to grant globally, now through `institutions.update.padalinys`.
 * The setting itself is deleted by the settings migration that follows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $payload = DB::table('settings')
            ->where('group', 'atstovavimas')
            ->where('name', 'institution_manager_role_id')
            ->value('payload');

        $roleId = is_string($payload) ? json_decode($payload, true) : null;

        if (! is_string($roleId) || $roleId === '') {
            return;
        }

        $role = Role::query()->find($roleId);

        if ($role === null) {
            return;
        }

        Duty::query()
            ->whereHas('roles', fn ($query) => $query->whereKey($role->id))
            ->with('institution:id,tenant_id')
            ->get()
            ->filter(fn (Duty $duty) => $duty->institution?->tenant_id !== null)
            ->each(fn (Duty $duty) => DB::table('duty_responsibilities')->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'duty_id' => $duty->id,
                'responsibility' => 'student_rep_coordination',
                'scope_type' => 'tenant',
                'scope_id' => (string) $duty->institution->tenant_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        if (Permission::query()->where('name', 'institutions.update.padalinys')->exists() && ! $role->hasPermissionTo('institutions.update.padalinys')) {
            $role->givePermissionTo('institutions.update.padalinys');

            // Same as RoleTypeObserver: these bypass the observers that usually clear per-user caches.
            $role->usersThroughDuties->merge($role->users)->each(function (User $user): void {
                PermissionMapBuilder::forgetCachedMaps($user->id);
                Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
            });
        }
    }

    public function down(): void
    {
        // Backfilled rows cannot be distinguished from assignments administrators later created.
    }
};
