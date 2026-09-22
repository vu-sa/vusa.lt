<?php

use App\Models\Banner;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->plainUser = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->banner = Banner::factory()->create([
        'title' => 'Pirmas baneris',
        'tenant_id' => $this->tenant->id,
        'is_active' => true,
    ]);
});

test('returns 403 to a user without banner read access', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.banners.index'))
        ->assertForbidden();
});

test('returns paginated banner collection contract for authorized user', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.banners.index', ['per_page' => 1]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
});

test('filters banners by search query', function (): void {
    Banner::factory()->create([
        'title' => 'Unikalus baneris',
        'tenant_id' => $this->tenant->id,
    ]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.banners.index', ['search' => 'Unikalus']))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.title', 'Unikalus baneris');
});
