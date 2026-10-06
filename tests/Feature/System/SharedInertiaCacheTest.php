<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

test('saving a tenant forgets the cached tenant list', function (): void {
    Cache::forever(HandleInertiaRequests::TENANTS_CACHE_KEY, 'stale');

    $tenant = Tenant::query()->first();
    $tenant->update(['shortname' => $tenant->shortname.' X']);

    expect(Cache::get(HandleInertiaRequests::TENANTS_CACHE_KEY))->toBeNull();
});

test('saving an institution forgets the cached tenant list, which carries primary institutions', function (): void {
    Cache::forever(HandleInertiaRequests::TENANTS_CACHE_KEY, 'stale');

    Institution::factory()->create();

    expect(Cache::get(HandleInertiaRequests::TENANTS_CACHE_KEY))->toBeNull();
});

test('changing a tenant alias moves its cached alias lookup', function (): void {
    $tenant = Tenant::factory()->create(['alias' => 'senas']);

    expect(Tenant::forAlias('senas')?->is($tenant))->toBeTrue();

    $tenant->update(['alias' => 'naujas']);

    expect(Tenant::forAlias('senas'))->toBeNull()
        ->and(Tenant::forAlias('naujas')?->is($tenant))->toBeTrue();
});

test('saving an institution type forgets the cached institution type list', function (): void {
    Cache::forever(HandleInertiaRequests::INSTITUTION_TYPES_CACHE_KEY, 'stale');

    InstitutionType::factory()->create([]);

    expect(Cache::get(HandleInertiaRequests::INSTITUTION_TYPES_CACHE_KEY))->toBeNull();
});
