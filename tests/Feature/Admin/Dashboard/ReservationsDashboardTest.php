<?php

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::factory()->create();

    // Manages $this->tenant's resources, but not $this->otherTenant's.
    $this->manager = makeTenantUser('Išteklių administratorius', $this->tenant);

    // Holds a duty in the tenant, but no resources.update.padalinys — administers nothing.
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->myResource = Resource::factory()->for($this->tenant)->create();
    $this->foreignResource = Resource::factory()->for($this->otherTenant)->create();
});

/**
 * Attach a resource to a reservation in a given state.
 */
function attachResource(Reservation $reservation, Resource $resource, string $state, array $overrides = []): void
{
    $reservation->resources()->attach($resource->id, [
        'quantity' => 1,
        'start_time' => $overrides['start_time'] ?? now()->subDay(),
        'end_time' => $overrides['end_time'] ?? now()->addDays(3),
        'state' => $state,
        'returned_at' => $overrides['returned_at'] ?? null,
    ]);
}

describe('overview counts', function (): void {
    test('counts only the items in tenants the user manages', function (): void {
        $mine = Reservation::factory()->create(['name' => 'Uses my resource']);
        attachResource($mine, $this->myResource, 'created');

        $foreign = Reservation::factory()->create(['name' => 'Uses only a foreign resource']);
        attachResource($foreign, $this->foreignResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowReservations')
                ->where('managesResources', true)
                ->where('counts.waitingForMe', 1)
                ->where('counts.lentOut', 0)
                ->where('counts.overdue', 0)
            );
    });

    test('lent items and overdue items are counted apart', function (): void {
        $lent = Reservation::factory()->create();
        attachResource($lent, $this->myResource, 'lent');

        $late = Reservation::factory()->create();
        attachResource($late, $this->myResource, 'lent', ['end_time' => now()->subDay()]);

        $done = Reservation::factory()->create();
        attachResource($done, $this->myResource, 'returned', ['end_time' => now()->subDay()]);

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.lentOut', 2)
                ->where('counts.overdue', 1)
            );
    });

    test('mine counts only the user\'s own reservations that are still in flight', function (): void {
        $own = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($own, $this->myResource, 'created');

        $closed = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($closed, $this->myResource, 'returned');

        $someoneElses = Reservation::factory()->create();
        attachResource($someoneElses, $this->myResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(fn (Assert $page) => $page->where('counts.mine', 1));
    });

    test('the lists are deferred and load as one group', function (): void {
        $waiting = Reservation::factory()->create();
        attachResource($waiting, $this->myResource, 'created');

        $own = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($own, $this->myResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('waitingForMe')
                ->missing('myUpcoming')
                ->loadDeferredProps('secondary', fn (Assert $deferred) => $deferred
                    ->has('waitingForMe', 2)
                    ->has('myUpcoming', 1)
                    ->where('myUpcoming.0.id', $own->id)
                )
            );
    });
});

describe('table payload flags', function (): void {
    test('approvable is true only for the pivots whose tenant the user manages', function (): void {
        // A single reservation mixing both tenants' resources — the crux of the permission model.
        $mixed = Reservation::factory()->create();
        attachResource($mixed, $this->myResource, 'created');
        attachResource($mixed, $this->foreignResource, 'created');

        asUser($this->manager)->get(route('reservations.index'))
            ->assertInertia(function (Assert $page): void {
                $resources = collect($page->toArray()['props']['reservations']['data'][0]['resources'])
                    ->keyBy('id');

                expect($resources[$this->myResource->id]['pivot']['approvable'])->toBeTrue()
                    ->and($resources[$this->foreignResource->id]['pivot']['approvable'])->toBeFalse();
            });
    });

    test('cancellable is false once an item has been lent out', function (): void {
        $lent = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($lent, $this->myResource, 'lent');

        asUser($this->manager)->get(route('reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservations.data.0.resources.0.pivot.cancellable', false)
                ->where('reservations.data.0.resources.0.pivot.state', 'lent')
            );
    });

    test('cancellable is true for the owner while the item is still pending', function (): void {
        $pending = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($pending, $this->myResource, 'created');

        asUser($this->manager)->get(route('reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservations.data.0.resources.0.pivot.cancellable', true)
            );
    });

    test('the record page hands its row actions the same flags, per item', function (): void {
        $mixed = Reservation::factory()->create();
        attachResource($mixed, $this->myResource, 'created');
        attachResource($mixed, $this->foreignResource, 'created');

        asUser($this->manager)->get(route('reservations.show', $mixed))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $resources = collect($page->toArray()['props']['decisionTarget']['resources'])->keyBy('id');

                expect($resources[$this->myResource->id]['pivot']['approvable'])->toBeTrue()
                    ->and($resources[$this->foreignResource->id]['pivot']['approvable'])->toBeFalse()
                    ->and($resources[$this->myResource->id]['pivot']['cancellable'])->toBeFalse();
            });
    });

    test('the API serves the same flags as the page', function (): void {
        $pending = Reservation::factory()->create();
        attachResource($pending, $this->myResource, 'created');

        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index'))
            ->assertOk()
            ->assertJsonPath('data.items.0.resources.0.pivot.approvable', true)
            ->assertJsonPath('data.items.0.resources.0.pivot.backtrackable', false)
            ->assertJsonPath('data.items.0.resources.0.pivot.cancellable', false);
    });

    test('backtrackable requires a managed resource with an active approved decision', function (): void {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'reserved');
        $pivot = ReservationResource::query()->where('reservation_id', $reservation->id)->firstOrFail();

        $approval = Approval::factory()->create([
            'approvable_type' => MorphMap::alias(ReservationResource::class),
            'approvable_id' => (string) $pivot->id,
            'user_id' => $this->manager->id,
            'decision' => ApprovalDecision::Approved,
        ]);

        asUser($this->manager)->get(route('reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservations.data.0.resources.0.pivot.backtrackable', true)
            );

        $approval->update(['reverted_at' => now(), 'reverted_by_id' => $this->manager->id]);

        asUser($this->manager)->get(route('reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservations.data.0.resources.0.pivot.backtrackable', false)
            );
    });
});

describe('fully resolving', function (): void {
    test('drives a pending resource straight to returned in one request', function (): void {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'created');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
            'notes' => 'Never collected, closing out.',
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('returned')
            ->and($pivot->returned_at)->not->toBeNull();
        // Fast-forwarding must not skip the audit trail: created→reserved→lent→returned.
        expect($pivot->approvals()->count())->toBe(3);
        expect($pivot->approvals()->first()->notes)->toBe('Never collected, closing out.');
    });

    test('resolves a lent resource with the single remaining step', function (): void {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'lent');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('returned')
            ->and($pivot->approvals()->count())->toBe(1);
    });

    test('refuses to resolve a resource belonging to a tenant the user does not manage', function (): void {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->foreignResource, 'created');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('created')
            ->and($pivot->approvals()->count())->toBe(0);
    });

    test('leaves an already terminal resource untouched', function (): void {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'returned');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('returned')
            ->and($pivot->approvals()->count())->toBe(0);
    });
});

describe('non-manager access', function (): void {
    test('a role without resource managership can open the overview and administers nothing', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowReservations')
                ->where('managesResources', false)
                ->where('counts.waitingForMe', 0)
            );
    });

    test('a user managing no resources sees none of the administered items', function (): void {
        $plain = makeUser($this->tenant);

        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'created');

        asUser($plain)->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('managesResources', false)
                ->where('counts.waitingForMe', 0)
                ->where('counts.mine', 0)
            );
    });

    test('guests are redirected', function (): void {
        $this->get(route('dashboard.reservations'))->assertRedirect();
    });
});

describe('index filters', function (): void {
    beforeEach(function (): void {
        $this->pending = Reservation::factory()->create(['name' => 'Pending']);
        attachResource($this->pending, $this->myResource, 'created');

        $this->foreignPending = Reservation::factory()->create(['name' => 'Foreign pending']);
        attachResource($this->foreignPending, $this->foreignResource, 'created');

        $this->lentLate = Reservation::factory()->hasAttached($this->manager)->create(['name' => 'Lent late']);
        attachResource($this->lentLate, $this->myResource, 'lent', ['end_time' => now()->subDay()]);
    });

    test('state narrows to reservations holding an item in that state', function (): void {
        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index', ['state' => ['lent']]))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $this->lentLate->id);
    });

    test('the page URL form state=a,b is accepted', function (): void {
        asUser($this->manager)->get(route('reservations.index', ['state' => 'created,lent']))
            ->assertInertia(fn (Assert $page) => $page->has('reservations.data', 3));
    });

    test('scope=administered only counts items in managed tenants', function (): void {
        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index', ['scope' => 'administered', 'state' => ['created']]))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $this->pending->id);
    });

    test('scope=mine only lists the user\'s own reservations', function (): void {
        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index', ['scope' => 'mine']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $this->lentLate->id);
    });

    test('overdue keeps in-flight items whose window has passed', function (): void {
        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index', ['overdue' => 1]))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $this->lentLate->id);
    });

    test('an unknown state or scope is rejected', function (): void {
        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index', ['state' => ['nonsense']]))
            ->assertUnprocessable();
        $this->actingAs($this->manager)->getJson(route('api.v1.admin.reservations.index', ['scope' => 'everyone']))
            ->assertUnprocessable();
    });
});
