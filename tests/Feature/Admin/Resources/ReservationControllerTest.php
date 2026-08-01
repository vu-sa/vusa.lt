<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\States\ReservationResource\Cancelled;
use App\States\ReservationResource\Created;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->user = makeUser($this->tenant);

    $this->resources = Resource::factory()->for($this->tenant)->count(3)->create();

    $this->admin = User::factory()->create();

    $resourceManagerDuty = Duty::factory()->has(Institution::factory()->state(
        ['tenant_id' => $this->tenant->id]
    ))->hasAttached($this->admin, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])->create();

    $resourceManagerDuty->assignRole('Resource Manager');

    $this->reservation = Reservation::factory()->hasAttached($this->resources)->create();

    $this->reservationManager = User::factory()->hasAttached($this->reservation)->create();
});

describe('index activeReservations', function (): void {
    test('indexes and dedupes a reservation spanning multiple resources to a single entry', function (): void {
        // $this->reservation is attached to 3 resources in beforeEach; the payload
        // must contain it exactly once (the refactor replaced PHP-side unique()).
        asUser($this->admin)->get(route('reservations.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reservations/IndexReservation')
                ->has('activeReservations', 1)
                ->where('activeReservations.0.id', $this->reservation->id)
                ->has('activeReservations.0.resources')
                ->has('activeReservations.0.users')
            );
    });
});

describe('auth: simple user', function (): void {
    beforeEach(function (): void {
        asUser($this->user)->get(route('dashboard'));
    });

    test('can\'t index reservations', function (): void {
        asUser($this->user)->get(route('reservations.index'))->assertStatus(403);
    });

    test('can access reservation create page', function (): void {
        asUser($this->user)->get(route('reservations.create'))->assertStatus(200);
    });

    test('can store reservation', function (): void {
        asUser($this->user)->get(route('reservations.create'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/CreateReservation')
            ->whereNot('resources', null)
        );

        $reservation = Reservation::factory()->make([
            'name' => 'test',
            'resources' => $this->resources->map(fn ($resource) => ['id' => $resource->id, 'quantity' => 1])->toArray(),
        ]);

        $response = asUser($this->user)->post(route('reservations.store'),
            $reservation->toArray()
        );

        $response->assertRedirect();

        $this->followRedirects($response)
            ->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.name', $reservation->name)
            );
    });

    test('can\'t access existing reservation', function (): void {

        $response = asUser($this->user)->get(route('reservations.show', $this->reservation->id));

        $response->assertStatus(403);
    });
    test('can access reservation after they are assigned to it', function (): void {
        $response = asUser($this->reservationManager)->put(route('reservations.add-users', $this->reservation->id), [
            'users' => [$this->user->id],
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->followRedirects($response)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->where('flash.success', __('messages.users_attached_to_reservation'))
            );

        $response = asUser($this->user)->get(route('reservations.show', $this->reservation->id));

        $this->followRedirects($response)->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.id', $this->reservation->id)
        );
    });

    test('can update reservation resource state from created to cancelled after they are assigned to it', function (): void {
        $reservation = Reservation::factory()->has(Resource::factory())->create();

        $resource = $reservation->resources->first();

        $reservation->users()->attach($this->user->id);

        asUser($this->user)->get(route('reservations.show', $reservation->id));

        $response = $this->post(route('approvals.store'), [
            'approvable_type' => 'reservation_resource',
            'approvable_id' => (string) $resource->pivot->id,
            'decision' => 'cancelled',
        ]);

        // Check if the response has an error flash message
        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionHas('success');

        $resource = $reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource is in canceled state
        expect($resource->pivot->state::class)->toEqual(Cancelled::class);
    });

    test('can\'t delete reservation', function (): void {
        $reservation = Reservation::query()->first();

        asUser($this->user)->delete(route('reservations.destroy', $reservation))->assertStatus(403);
    });
});

describe('auth: reservation manager', function (): void {
    beforeEach(function (): void {
        asUser($this->reservationManager)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index reservations', function (): void {
        asUser($this->reservationManager)->get(route('reservations.index'))->assertStatus(403);
    });

    test('can access reservation create page', function (): void {
        asUser($this->reservationManager)->get(route('reservations.create'))->assertStatus(200);
    });

    test('can store reservation', function (): void {
        asUser($this->reservationManager)->get(route('reservations.create'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/CreateReservation')
            ->whereNot('resources', null)
        );

        $reservation = Reservation::factory()->make([
            'name' => 'test',
            'resources' => $this->resources->map(fn ($resource) => ['id' => $resource->id, 'quantity' => 1])->toArray(),
        ]);

        $response = asUser($this->reservationManager)->post(route('reservations.store'),
            $reservation->toArray()
        );

        $response->assertRedirect();

        $this->followRedirects($response)
            ->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.name', $reservation->name)
            );
    });

    test('can access existing reservation', function (): void {
        $response = asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.id', $this->reservation->id)
        );
    });

    test('can update reservation resource state from created to cancelled', function (): void {
        $resource = $this->reservation->resources->first();

        asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id))
            ->assertStatus(200);

        $response = $this->post(route('approvals.store'), [
            'approvable_type' => 'reservation_resource',
            'approvable_id' => (string) $resource->pivot->id,
            'decision' => 'cancelled',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionHas('success');

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource is in canceled state
        expect($resource->pivot->state::class)->toEqual(Cancelled::class);
    });

    test('can\'t update reservation resource state from created to reserved', function (): void {
        $resource = $this->reservation->resources->first();

        // assert that the resource is in created state
        expect($resource->pivot->state::class)->toEqual(Created::class);

        asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id))
            ->assertStatus(200);

        $response = asUserWithInertia($this->reservationManager)->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertRedirect(route('reservations.show', $this->reservation->id));

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in created state
        expect($resource->pivot->state::class)->toEqual(Created::class);
    });

    test('can update reservation resource state from created to rejected', function (): void {
        $resource = $this->reservation->resources->first();

        // assert that the resource is in created state
        expect($resource->pivot->state::class)->toEqual(Created::class);

        asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id))
            ->assertStatus(200);

        $response = asUserWithInertia($this->reservationManager)->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'reject',
        ]);

        $response->assertRedirect(route('reservations.show', $this->reservation->id));

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in created state
        expect($resource->pivot->state::class)->toEqual(Created::class);
    });

    test('can update reservation resource state from reserved to lent', function (): void {
        $resource = Resource::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->reservation->resources()->attach($resource->id, ['quantity' => 1, 'state' => 'reserved']);

        $this->actingAs($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response = asUserWithInertia($this->reservationManager)->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertRedirect(route('reservations.show', $this->reservation->id));

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in reserved state
        expect($resource->pivot->state::class)->toEqual(Reserved::class);
    });

    test('can update reservation resource state from lent to returned', function (): void {
        $resource = Resource::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->reservation->resources()->attach($resource->id, ['quantity' => 1, 'state' => 'lent']);

        $this->actingAs($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response = asUserWithInertia($this->reservationManager)->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertRedirect(route('reservations.show', $this->reservation->id));

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in lent state
        expect($resource->pivot->state::class)->toEqual(Lent::class);
    });

    test('cannot update reservation resource state that has already been cancelled', function (): void {
        $resource = $this->reservation->resources->first();

        $resource->pivot->update([
            'state' => 'cancelled',
        ]);

        $this->actingAs($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response = asUserWithInertia($this->reservationManager)->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $resource->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertRedirect(route('reservations.show', $this->reservation->id));

        // assert that the resource is still in cancelled state
        expect($resource->pivot->state::class)->toEqual(Cancelled::class);
    });

    test('can\'t delete reservation', function (): void {
        asUser($this->reservationManager)->delete(route('reservations.destroy', $this->reservation))->assertRedirectToRoute('dashboard');
    });
});
