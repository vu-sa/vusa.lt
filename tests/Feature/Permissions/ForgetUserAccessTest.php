<?php

use App\Actions\ForgetUserAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Services\InstitutionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('forgetting access clears every derived cache only after commit', function (): void {
    $user = User::factory()->create();
    $keys = [
        'auth:duties:'.$user->id,
        'permission-maps:v1:'.$user->id,
        'index-permissions-'.$user->id,
        'create-permissions-'.$user->id,
        'force-delete-permissions-'.$user->id,
        HandleInertiaRequests::adminNavigationCacheKey($user->id),
        InstitutionAccessService::getAccessCacheKey($user->id),
        InstitutionAccessService::getAccessCacheKey($user->id, includeRelated: false),
        'typesense_scoped_keys:'.$user->id,
    ];
    foreach ($keys as $key) {
        Cache::put($key, ['stale']);
    }
    Cache::put('index-permissions-another-user', ['untouched']);

    DB::transaction(function () use ($user, $keys): void {
        ForgetUserAccess::execute([$user->id, $user->id]);

        foreach ($keys as $key) {
            expect(Cache::get($key))->toBe(['stale']);
        }
    });

    foreach ($keys as $key) {
        expect(Cache::has($key))->toBeFalse();
    }
    expect(Cache::get('index-permissions-another-user'))->toBe(['untouched']);
});

test('rolling back an access change preserves its caches', function (): void {
    $user = User::factory()->create();
    $key = HandleInertiaRequests::adminNavigationCacheKey($user->id);
    Cache::put($key, ['current']);

    DB::beginTransaction();
    try {
        ForgetUserAccess::execute([$user->id]);
    } finally {
        DB::rollBack();
    }
    DB::transaction(fn () => null);

    expect(Cache::get($key))->toBe(['current']);
});
