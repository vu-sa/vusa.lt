<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use App\States\ReservationResource\Cancelled;
use App\States\ReservationResource\Created;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Rejected;
use App\States\ReservationResource\Reserved;
use App\States\ReservationResource\Returned;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->resourcePadalinys = Padalinys::inRandomOrder()->first();

    $this->resources = Resource::factory()->for($this->resourcePadalinys)->count(3)->create();
    $this->reservation = Reservation::factory()->hasAttached($this->resources)->create();

    $this->simpleUser = User::factory()->create();
    $this->reservationManagerUser = User::factory()->hasAttached($this->reservation)->create();
    $this->resourceManagerUser = User::factory()->create();

    $resourceManagerDuty = Duty::factory()->has(Institution::factory()->state(
        ['padalinys_id' => $this->resourcePadalinys->id]
    ))->hasAttached($this->resourceManagerUser, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])->create();

    $resourceManagerRole = Role::factory()->create();
    $resourceManagerRole->givePermissionTo(['reservations.read.*', config('permission.resource_managership_indicating_permission')]);

    $resourceManagerDuty->assignRole($resourceManagerRole);

});

test('simple user cannot access reservation', function () {
    $user = $this->simpleUser;
    $reservation = $this->reservation;

    // TODO: why first page visit is needed for flashing?
    $this->actingAs($user)->get(route('dashboard'));

    $response = $this->actingAs($user)->get(route('reservations.show', $reservation->id));

    $response->assertStatus(302)->assertRedirect(route('dashboard'));

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowDashboard')
            ->whereNot('flash.info', null)
            ->where('flash.info', 'This action is unauthorized.')
        );
});

test('simple user cannot update reservation', function () {
    $user = $this->simpleUser;
    $reservation = $this->reservation;

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this->actingAs($user)->patch(route('reservations.update', $reservation->id), [
        ...$reservation->toArray(),
        'start_time' => now()->addHours(1),
        'end_time' => now()->addHours(2),
    ]);

    $response->assertStatus(302)->assertRedirect(route('dashboard'));

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowDashboard')
            ->whereNot('flash.info', null)
            ->where('flash.info', 'This action is unauthorized.')
        );
});

test('simple user can access reservation after they are assigned to it', function () {
    $user = $this->simpleUser;
    $reservation = $this->reservation;

    // first assign the user to the reservation
    $this->actingAs($this->reservationManagerUser)->get(route('dashboard'));

    $response = $this->actingAs($this->reservationManagerUser)->put(route('reservations.add-users', $reservation->id), [
        'users' => [$user->id],
    ]);

    $response->assertStatus(302)->assertRedirect(route('dashboard'));

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowDashboard')
            ->where('flash.success', __('messages.users_attached_to_reservation'))
        );

    $response = $this->actingAs($user)->get(route('reservations.show', $reservation->id));

    $this->followRedirects($response)->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Reservations/ShowReservation')
        ->whereNot('reservation', null)
        ->where('reservation.id', $reservation->id)
    );
});

test('simple user can create reservation', function () {
    $user = $this->simpleUser;

    $this->actingAs($user)->get(route('reservations.create'))->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Reservations/CreateReservation')
        ->whereNot('resources', null)
    );

    $reservation = Reservation::factory()->make([
        'name' => 'test',
        'resources' => $this->resources->map(fn ($resource) => ['id' => $resource->id, 'quantity' => 1])->toArray(),
    ]);

    $response = $this->actingAs($user)->post(route('reservations.store'),
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

test('simple user can update reservation resource state from created to cancelled after they are assigned to it', function () {
    $user = $this->simpleUser;

    $reservation = Reservation::factory()->has(Resource::factory())->create();

    $resource = $reservation->resources->first();

    $reservation->users()->attach($user->id);

    $this->actingAs($user)->get(route('reservations.show', $reservation->id));

    $response = $this->post(route('users.comments.store', $user->id), [
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

test('resource manager can update reservation resource state from created to reserved', function () {
    $user = $this->resourceManagerUser;

    $this->actingAs($user)->get(route('reservations.show', $this->reservation->id));

    $resource = $this->reservation->resources->first();

    // assert that the resource is in created state
    expect(get_class($resource->pivot->state))->toEqual(Created::class);

    $response = $this->post(route('users.comments.store', $user->id), [
        'commentable_type' => 'reservation_resource',
        'commentable_id' => $this->reservation->resources->first()->pivot->id,
        'comment' => 'test',
        'decision' => 'approve',
    ]);

    $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
    $this->followRedirects($response)->assertStatus(200);

    $resource = $this->reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

    // assert that the resource is in reserved state
    expect(get_class($resource->pivot->state))->toEqual(Reserved::class);
});

test('resource manager can update reservation resource state from reserved to lent', function () {
    $user = $this->resourceManagerUser;

    $reservation = Reservation::factory()->create();

    $resource = Resource::factory()->create(['padalinys_id' => $this->resourcePadalinys->id]);

    $reservation->resources()->attach($resource->id, ['quantity' => 1, 'state' => 'reserved']);

    $this->actingAs($user)->get(route('reservations.show', $reservation->id));

    $response = $this->post(route('users.comments.store', $user->id), [
        'commentable_type' => 'reservation_resource',
        'commentable_id' => $reservation->resources->first()->pivot->id,
        'comment' => 'test',
        'decision' => 'approve',
    ]);

    $response->assertStatus(302)->assertRedirect(route('reservations.show', $reservation->id));
    $this->followRedirects($response)->assertStatus(200);

    $resource = $reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

    // assert that the resource is in lent state
    expect(get_class($resource->pivot->state))->toEqual(Lent::class);
});

test('resource manager can update reservation resource state from lent to returned', function () {
    $user = $this->resourceManagerUser;

    $reservation = Reservation::factory()->create();

    $resource = Resource::factory()->create(['padalinys_id' => $this->resourcePadalinys->id]);

    $reservation->resources()->attach($resource->id, ['quantity' => 1, 'state' => 'lent']);

    $this->actingAs($user)->get(route('reservations.show', $reservation->id));

    $response = $this->post(route('users.comments.store', $user->id), [
        'commentable_type' => 'reservation_resource',
        'commentable_id' => $reservation->resources->first()->pivot->id,
        'comment' => 'test',
        'decision' => 'approve',
    ]);

    $response->assertStatus(302)->assertRedirect(route('reservations.show', $reservation->id));
    $this->followRedirects($response)->assertStatus(200);

    $resource = $reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

    // assert that the resource is in returned state
    expect(get_class($resource->pivot->state))->toEqual(Returned::class);
});

test('resource manager can update reservation resource state from created to rejected', function () {
    $user = $this->resourceManagerUser;

    $reservation = Reservation::factory()->create();

    $resource = Resource::factory()->create(['padalinys_id' => $this->resourcePadalinys->id]);

    $reservation->resources()->attach($resource->id, ['quantity' => 1]);

    $this->actingAs($user)->get(route('reservations.show', $reservation->id));

    $response = $this->post(route('users.comments.store', $user->id), [
        'commentable_type' => 'reservation_resource',
        'commentable_id' => $reservation->resources->first()->pivot->id,
        'comment' => 'test',
        'decision' => 'reject',
    ]);

    $response->assertStatus(302)->assertRedirect(route('reservations.show', $reservation->id));
    $this->followRedirects($response)->assertStatus(200);

    $resource = $reservation->load(['resources' => fn ($query) => $query->where('resources.id', $resource->id)])->resources->first();

    // assert that the resource is in rejected state
    expect(get_class($resource->pivot->state))->toEqual(Rejected::class);
});

test('resource manager cannot update reservation resource state that has already been cancelled', function () {
    $user = $this->resourceManagerUser;

    $this->actingAs($user)->get(route('reservations.show', $this->reservation->id));

    $resource = $this->reservation->resources->first();

    $resource->pivot->update([
        'state' => 'cancelled',
    ]);

    $response = $this->post(route('users.comments.store', $user->id), [
        'commentable_type' => 'reservation_resource',
        'commentable_id' => $resource->pivot->id,
        'comment' => 'test',
        'decision' => 'approve',
    ]);

    $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
    $this->followRedirects($response)->assertStatus(200);

    // assert that the resource is still in cancelled state

    // TODO: should test the response
    expect(get_class($resource->pivot->state))->toEqual(Cancelled::class);
});
