<?php

use App\Models\Problem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    [$this->tenant, $this->otherTenant] = Tenant::query()->inRandomOrder()->take(2)->get();

    $this->user = makeUser($this->tenant);
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    $this->problem = Problem::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->coordinator->id,
    ]);

    $this->otherTenantProblem = Problem::factory()->create([
        'tenant_id' => $this->otherTenant->id,
    ]);
});

describe('unauthorized access', function () {
    test('cannot access problem index', function () {
        asUser($this->user)->get(route('problems.index'))->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access problem index', function () {
        asUser($this->coordinator)->get(route('problems.index'))->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Problems/IndexProblem')
                ->has('data')
                ->has('categories')
                ->has('institutions')
            );
    });

    test('can filter problems by tenant', function () {
        asUser($this->admin)
            ->get(route('problems.index', ['filters' => json_encode(['tenant.id' => [$this->tenant->id]])]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Problems/IndexProblem')
                ->where('data', function ($data) {
                    return collect($data)->isNotEmpty()
                        && collect($data)->every(fn ($problem) => $problem['tenant_id'] === $this->tenant->id);
                })
            );
    });

    test('can filter problems by creator', function () {
        asUser($this->admin)
            ->get(route('problems.index', ['filters' => json_encode(['created_by' => [$this->coordinator->id]])]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Problems/IndexProblem')
                ->where('data', function ($data) {
                    return collect($data)->isNotEmpty()
                        && collect($data)->every(fn ($problem) => $problem['created_by']['id'] === $this->coordinator->id);
                })
            );
    });
});

describe('tenant isolation', function () {
    test('tenant-scoped user only sees own tenant problems', function () {
        asUser($this->coordinator)->get(route('problems.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('data', function ($data) {
                    return collect($data)->isNotEmpty()
                        && collect($data)->every(fn ($problem) => $problem['tenant_id'] === $this->tenant->id);
                })
            );
    });

    test('tenant-scoped user cannot see other tenant problems even when filtering for them', function () {
        asUser($this->coordinator)
            ->get(route('problems.index', ['filters' => json_encode(['tenant.id' => [$this->otherTenant->id]])]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('data', function ($data) {
                    return collect($data)->every(fn ($problem) => $problem['tenant_id'] === $this->tenant->id);
                })
            );
    });
});
