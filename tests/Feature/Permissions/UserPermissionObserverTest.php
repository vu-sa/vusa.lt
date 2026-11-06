<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('a role permission change clears maps and navigation for direct and duty holders', function (bool $pastHolder): void {
    $this->freezeTime();
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $duty = Duty::factory()->for($institution)->create();
    $role = Role::factory()->create();
    $permission = Permission::findOrCreate('news.update.padalinys', 'web');
    $role->givePermissionTo($permission);
    $duty->assignRole($role);
    $holder = User::factory()->create();
    Dutiable::factory()->forDuty($duty)->forUser($holder)->create([
        'start_date' => today()->subMonth(),
        'end_date' => $pastHolder ? today()->subDay() : null,
    ]);
    $directHolder = User::factory()->create();
    $directHolder->assignRole($role);
    $keys = collect([$holder, $directHolder])->flatMap(fn (User $user): array => [
        'permission-maps:v1:'.$user->id,
        'index-permissions-'.$user->id,
        'create-permissions-'.$user->id,
        'force-delete-permissions-'.$user->id,
        HandleInertiaRequests::adminNavigationCacheKey($user->id),
    ]);
    foreach ($keys as $key) {
        Cache::put($key, ['stale']);
    }

    DB::transaction(function () use ($role, $permission, $keys): void {
        $role->revokePermissionTo($permission);
        $role->touch();

        foreach ($keys as $key) {
            expect(Cache::get($key))->toBe(['stale']);
        }
    });

    foreach ($keys as $key) {
        expect(Cache::has($key))->toBeFalse();
    }
})->with(['current holder' => false, 'past holder' => true]);
