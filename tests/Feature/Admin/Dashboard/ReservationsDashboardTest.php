<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::factory()->create();

    // Manages $this->tenant's resources, but not $this->otherTenant's.
    $this->manager = makeTenantUser('Resource Manager', $this->tenant);

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

describe('scoping', function () {
    test('administered list only holds reservations touching resources the user manages', function () {
        $mine = Reservation::factory()->create(['name' => 'Uses my resource']);
        attachResource($mine, $this->myResource, 'created');

        $foreign = Reservation::factory()->create(['name' => 'Uses only a foreign resource']);
        attachResource($foreign, $this->foreignResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowReservations')
                ->has('administeredReservations', 1)
                ->where('administeredReservations.0.id', $mine->id)
            );
    });

    test('approvable is true only for the pivots whose tenant the user manages', function () {
        // A single reservation mixing both tenants' resources — the crux of the permission model.
        $mixed = Reservation::factory()->create();
        attachResource($mixed, $this->myResource, 'created');
        attachResource($mixed, $this->foreignResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(function (Assert $page) {
                $resources = collect($page->toArray()['props']['administeredReservations'][0]['resources'])
                    ->keyBy('id');

                expect($resources[$this->myResource->id]['pivot']['approvable'])->toBeTrue();
                expect($resources[$this->foreignResource->id]['pivot']['approvable'])->toBeFalse();
            });
    });

    test('my reservations lists only the user\'s own bookings', function () {
        $own = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($own, $this->myResource, 'created');

        $someoneElses = Reservation::factory()->create();
        attachResource($someoneElses, $this->myResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('myReservations', 1)
                ->where('myReservations.0.id', $own->id)
                // The other reservation still shows up as something to administer.
                ->has('administeredReservations', 2)
            );
    });

    test('cancellable is false once an item has been lent out', function () {
        $lent = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($lent, $this->myResource, 'lent');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('myReservations.0.resources.0.pivot.cancellable', false)
                ->where('myReservations.0.resources.0.pivot.state', 'lent')
            );
    });

    test('cancellable is true for the owner while the item is still pending', function () {
        $pending = Reservation::factory()->hasAttached($this->manager)->create();
        attachResource($pending, $this->myResource, 'created');

        asUser($this->manager)->get(route('dashboard.reservations'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('myReservations.0.resources.0.pivot.cancellable', true)
            );
    });
});

describe('fully resolving', function () {
    test('drives a pending resource straight to returned in one request', function () {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'created');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
            'notes' => 'Never collected, closing out.',
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('returned');
        expect($pivot->returned_at)->not->toBeNull();
        // Fast-forwarding must not skip the audit trail: created→reserved→lent→returned.
        expect($pivot->approvals()->count())->toBe(3);
        expect($pivot->approvals()->first()->notes)->toBe('Never collected, closing out.');
    });

    test('resolves a lent resource with the single remaining step', function () {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'lent');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('returned');
        expect($pivot->approvals()->count())->toBe(1);
    });

    test('refuses to resolve a resource belonging to a tenant the user does not manage', function () {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->foreignResource, 'created');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('created');
        expect($pivot->approvals()->count())->toBe(0);
    });

    test('leaves an already terminal resource untouched', function () {
        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'returned');

        $pivot = $reservation->resources->first()->pivot;

        asUser($this->manager)->post(route('approvals.resolve'), [
            'approvable_type' => 'reservation_resource',
            'approvable_ids' => [(string) $pivot->id],
        ])->assertRedirect();

        $pivot->refresh();

        expect($pivot->state->getValue())->toBe('returned');
        expect($pivot->approvals()->count())->toBe(0);
    });
});

describe('auth', function () {
    test('a user managing no resources sees an empty administered list', function () {
        $plain = makeUser($this->tenant);

        $reservation = Reservation::factory()->create();
        attachResource($reservation, $this->myResource, 'created');

        asUser($plain)->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('administeredReservations', 0)
                ->has('managedTenants', 0)
            );
    });

    test('guests are redirected', function () {
        $this->get(route('dashboard.reservations'))->assertRedirect();
    });
});
