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

uses(RefreshDatabase::class);
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use Inertia\Testing\AssertableInertia as Assert;


beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

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

describe('auth: simple user', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'));
    });

    test('can\'t index reservations', function () {
        asUser($this->user)->get(route('reservations.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can access reservation create page', function () {
        asUser($this->user)->get(route('reservations.create'))->assertStatus(200);
    });

    test('can store reservation', function () {
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

        $response->assertStatus(302);

        $this->followRedirects($response)
            ->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.name', $reservation->name)
            );
    });

    test('can\'t access existing reservation', function () {

        $response = asUser($this->user)->get(route('reservations.show', $this->reservation->id));

        $response->assertStatus(302)->assertRedirect(route('dashboard'));

        $this->followRedirects($response)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->whereNot('flash.statusCode', null)
                ->where('flash.statusCode', 403)
            );
    });
    test('can access reservation after they are assigned to it', function () {
        $response = asUser($this->reservationManager)->put(route('reservations.add-users', $this->reservation->id), [
            'users' => [$this->user->id],
        ]);

        $response->assertStatus(302)->assertRedirect(route('dashboard'));

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

    test('can update reservation resource state from created to cancelled after they are assigned to it', function () {
        $reservation = Reservation::factory()->has(Resource::factory())->create();

        $resource = $reservation->resources->first();

        $reservation->users()->attach($this->user->id);

        asUser($this->user)->get(route('reservations.show', $reservation->id));

        $response = $this->post(route('users.comments.store', $this->user->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $resource->pivot->id,
            'comment' => 'test',
            'decision' => 'cancel',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $reservation->id));

        $resource = $reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource is in canceled state
        expect(get_class($resource->pivot->state))->toEqual(Cancelled::class);
    });

    test('can\'t delete reservation', function () {
        $reservation = Reservation::query()->first();

        asUser($this->user)->delete(route('reservations.destroy', $reservation))->assertStatus(302);
    });
});

describe('auth: reservation manager', function () {
    beforeEach(function () {
        asUser($this->reservationManager)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index reservations', function () {
        asUser($this->reservationManager)->get(route('reservations.index'))->assertStatus(302);
    });

    test('can access reservation create page', function () {
        asUser($this->reservationManager)->get(route('reservations.create'))->assertStatus(200);
    });

    test('can store reservation', function () {
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

        $response->assertStatus(302);

        $this->followRedirects($response)
            ->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.name', $reservation->name)
            );
    });

    test('can access existing reservation', function () {
        $response = asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.id', $this->reservation->id)
        );
    });

    test('can update reservation resource state from created to cancelled', function () {
        $resource = $this->reservation->resources->first();

        asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id))
            ->assertStatus(200);

        $response = $this->post(route('users.comments.store', $this->reservation->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $resource->pivot->id,
            'comment' => 'test',
            'decision' => 'cancel',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource is in canceled state
        expect(get_class($resource->pivot->state))->toEqual(Cancelled::class);
    });

    test('can\'t update reservation resource state from created to reserved', function () {
        $resource = $this->reservation->resources->first();

        // assert that the resource is in created state
        expect(get_class($resource->pivot->state))->toEqual(Created::class);

        asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id))
            ->assertStatus(200);

        $response = $this->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
        $this->followRedirects($response)->assertStatus(200);

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in created state
        expect(get_class($resource->pivot->state))->toEqual(Created::class);
    });

    test('can update reservation resource state from created to rejected', function () {
        $resource = $this->reservation->resources->first();

        // assert that the resource is in created state
        expect(get_class($resource->pivot->state))->toEqual(Created::class);

        asUser($this->reservationManager)->get(route('reservations.show', $this->reservation->id))
            ->assertStatus(200);

        $response = $this->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'reject',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
        $this->followRedirects($response)->assertStatus(200);

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in created state
        expect(get_class($resource->pivot->state))->toEqual(Created::class);
    });

    test('can update reservation resource state from reserved to lent', function () {
        $resource = Resource::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->reservation->resources()->attach($resource->id, ['quantity' => 1, 'state' => 'reserved']);

        $this->actingAs($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response = $this->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
        $this->followRedirects($response)->assertStatus(200);

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in reserved state
        expect(get_class($resource->pivot->state))->toEqual(Reserved::class);
    });

    test('can update reservation resource state from lent to returned', function () {
        $resource = Resource::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->reservation->resources()->attach($resource->id, ['quantity' => 1, 'state' => 'lent']);

        $this->actingAs($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response = $this->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
        $this->followRedirects($response)->assertStatus(200);

        $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

        // assert that the resource stays in lent state
        expect(get_class($resource->pivot->state))->toEqual(Lent::class);
    });

    test('cannot update reservation resource state that has already been cancelled', function () {
        $resource = $this->reservation->resources->first();

        $resource->pivot->update([
            'state' => 'cancelled',
        ]);

        $this->actingAs($this->reservationManager)->get(route('reservations.show', $this->reservation->id));

        $response = $this->post(route('users.comments.store', $this->reservationManager->id), [
            'commentable_type' => 'reservation_resource',
            'commentable_id' => $resource->pivot->id,
            'comment' => 'test',
            'decision' => 'approve',
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
        $this->followRedirects($response)->assertStatus(200);

        // assert that the resource is still in cancelled state
        expect(get_class($resource->pivot->state))->toEqual(Cancelled::class);
    });

    test('can\'t delete reservation', function () {
        asUser($this->reservationManager)->delete(route('reservations.destroy', $this->reservation))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });
});
