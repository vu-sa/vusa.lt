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

describe('html sanitization', function () {
    /**
     * Problems are readable by every authenticated user while only tenant staff
     * may write them, and ShowProblem renders these fields with `v-html`. Script
     * stored here would otherwise execute in a super admin's browser.
     */
    test('strips script from tiptap fields when storing', function () {
        asUser($this->coordinator)->post(route('problems.store'), [
            'title' => ['lt' => 'Problema', 'en' => 'Problem'],
            'description' => [
                'lt' => '<p>Geras tekstas</p><script>alert(1)</script>',
                'en' => '<p>Good text</p><img src=x onerror="alert(1)">',
            ],
            'steps_taken' => ['lt' => '<p>Bandyta</p><script>alert(2)</script>'],
            'solution' => ['lt' => '<p>Spręsta</p><a href="javascript:alert(3)">x</a>'],
            'tenant_id' => $this->tenant->id,
            'occurred_at' => now()->subDay()->toDateString(),
            'status' => 'open',
        ])->assertRedirect();

        $problem = Problem::query()->whereJsonContainsLocale('title', 'lt', 'Problema')->sole();

        expect($problem->getTranslation('description', 'lt'))
            ->toContain('Geras tekstas')
            ->not->toContain('<script');

        expect($problem->getTranslation('description', 'en'))
            ->toContain('Good text')
            ->not->toContain('onerror');

        expect($problem->getTranslation('steps_taken', 'lt'))->not->toContain('<script');
        expect($problem->getTranslation('solution', 'lt'))->not->toContain('javascript:');
    });

    test('sanitizes on every write path, not just the controller', function () {
        $problem = Problem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => ['lt' => '<p>ok</p><script>alert(1)</script>'],
        ]);

        expect($problem->fresh()->getTranslation('description', 'lt'))->not->toContain('<script');

        $problem->update(['description' => ['lt' => '<img src=x onerror="alert(1)">']]);

        expect($problem->fresh()->getTranslation('description', 'lt'))->not->toContain('onerror');
    });

    test('keeps legitimate rich formatting', function () {
        $problem = Problem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => ['lt' => '<h2 id="a">Antraštė</h2><p><strong>Svarbu</strong></p><img src="/uploads/a.png" alt="A">'],
        ]);

        expect($problem->fresh()->getTranslation('description', 'lt'))
            ->toContain('<h2 id="a">Antraštė</h2>')
            ->toContain('<strong>Svarbu</strong>')
            ->toContain('src="/uploads/a.png"');
    });
});
