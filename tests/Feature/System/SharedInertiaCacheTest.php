<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Type;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

test('saving a tenant forgets the cached tenant list', function (): void {
    Cache::forever(HandleInertiaRequests::TENANTS_CACHE_KEY, 'stale');

    $tenant = Tenant::query()->first();
    $tenant->update(['shortname' => $tenant->shortname.' X']);

    expect(Cache::get(HandleInertiaRequests::TENANTS_CACHE_KEY))->toBeNull();
});

test('saving an institution type forgets the cached institution type list', function (): void {
    Cache::forever(HandleInertiaRequests::INSTITUTION_TYPES_CACHE_KEY, 'stale');

    Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);

    expect(Cache::get(HandleInertiaRequests::INSTITUTION_TYPES_CACHE_KEY))->toBeNull();
});
