<?php

use App\Models\Problem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    [$this->tenant, $this->otherTenant] = Tenant::query()->inRandomOrder()->take(2)->get();

    $this->plainUser = makeUser($this->tenant);
    $this->coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);

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

test('lists every padalinys\' problems to a member without a role', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.problems.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data.items');
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
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.items.0.status', 'resolved')
        ->assertJsonPath('data.items.1.status', 'resolved');
});

test('counts facet values across every padalinys, each facet ignoring its own selection', function (): void {
    Problem::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id,
        'status' => 'resolved',
    ]);

    // Selecting "open" narrows the list but not the status counts; the other padalinys' resolved problem counts too.
    asUser($this->coordinator)
        ->getJson(route('api.v1.admin.problems.index', [
            'status' => ['open'],
            'filters' => json_encode(['status' => ['open']]),
            'include_facets' => 1,
            'facet_values' => json_encode(['status' => ['open', 'resolved']]),
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.facets.status.open', 1)
        ->assertJsonPath('data.facets.status.resolved', 3);
});

test('leaves facet counts out unless the page asks for them', function (): void {
    asUser($this->coordinator)
        ->getJson(route('api.v1.admin.problems.index'))
        ->assertOk()
        ->assertJsonPath('data.facets', null);
});
