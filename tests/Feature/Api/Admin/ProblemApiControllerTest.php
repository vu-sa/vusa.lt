<?php

use App\Models\Problem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    [$this->tenant, $this->otherTenant] = Tenant::query()->inRandomOrder()->take(2)->get();

    $this->plainUser = makeUser($this->tenant);
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);

    $this->problem = Problem::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->coordinator->id,
        'status' => 'open',
    ]);

    $this->otherTenantProblem = Problem::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'status' => 'resolved',
    ]);
});

test('returns 403 to a user without problem read access', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.problems.index'))
        ->assertForbidden();
});

test('returns paginated problem collection contract for authorized coordinator', function (): void {
    asUser($this->coordinator)
        ->getJson(route('api.v1.admin.problems.index', ['per_page' => 1]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
});

test('filters problem collection by status', function (): void {
    Problem::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => 'resolved',
    ]);

    asUser($this->coordinator)
        ->getJson(route('api.v1.admin.problems.index', ['status' => ['resolved']]))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.status', 'resolved');
});
