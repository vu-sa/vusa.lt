<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
    $this->coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->member = makeUser($this->tenant);
});

/**
 * A seat in a tenant's institution, with an optional holder ending on the given date.
 */
function seatIn(Tenant $tenant, ?string $endsOn = null): Duty
{
    $duty = Duty::factory()->for(Institution::factory()->for($tenant), 'institution')->create();

    if ($endsOn !== null) {
        $duty->users()->attach(makeUser($tenant)->id, ['start_date' => now()->subYear()->toDateString(), 'end_date' => $endsOn]);
    }

    return $duty;
}

describe('access', function (): void {
    test('a member with no organisation sections is refused', function (): void {
        asUser($this->member)->get(route('dashboard.organizacija'))->assertStatus(403);
    });

    test('a coordinator opens it', function (): void {
        asUser($this->coordinator)->get(route('dashboard.organizacija'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowOrganizacija')
                ->has('counts')
                ->has('endingTerms')
                ->missing('recentlyEdited')
            );
    });

    test('guests are redirected', function (): void {
        $this->get(route('dashboard.organizacija'))->assertRedirect();
    });
});

describe('what it counts', function (): void {
    test('only the seats of tenants the user may read', function (): void {
        $before = asUser($this->coordinator)->get(route('dashboard.organizacija'))->viewData('page')['props']['counts']['duties'];

        seatIn($this->tenant);
        seatIn($this->otherTenant);

        asUser($this->coordinator)->get(route('dashboard.organizacija'))
            ->assertInertia(fn (Assert $page) => $page->where('counts.duties', $before + 1));
    });

    test('seats with nobody in them are counted apart', function (): void {
        $empty = asUser($this->coordinator)->get(route('dashboard.organizacija'))->viewData('page')['props']['counts']['emptyDuties'];

        seatIn($this->tenant);

        asUser($this->coordinator)->get(route('dashboard.organizacija'))
            ->assertInertia(fn (Assert $page) => $page->where('counts.emptyDuties', $empty + 1));
    });

    test('terms ending within thirty days are listed soonest first, later ones are not', function (): void {
        $soon = seatIn($this->tenant, now()->addDays(5)->toDateString());
        seatIn($this->tenant, now()->addDays(20)->toDateString());
        seatIn($this->tenant, now()->addDays(90)->toDateString());
        seatIn($this->otherTenant, now()->addDays(3)->toDateString());

        asUser($this->coordinator)->get(route('dashboard.organizacija'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.endingSoon', 2)
                ->has('endingTerms', 2)
                ->where('endingTerms.0.duty_id', (string) $soon->id)
            );
    });

    test('the recently edited list is deferred', function (): void {
        asUser($this->coordinator)->get(route('dashboard.organizacija'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $deferred) => $deferred->has('recentlyEdited'))
            );
    });
});
