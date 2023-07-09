<?php

namespace Tests\Feature\Reservations;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\TestCase;

#[CoversNothing]
class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private $simpleUser;

    private $reservationManagerUser;

    private $resourcePadalinys;

    private $resourceManagerUser;

    private $reservation;

    private $resources;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourcePadalinys = Padalinys::inRandomOrder()->first();

        $this->resources = Resource::factory()->for($this->resourcePadalinys)->count(3)->create();
        $this->reservation = Reservation::factory()->hasAttached($this->resources)->create();

        $this->simpleUser = User::factory()->create();
        $this->reservationManagerUser = User::factory()->hasAttached($this->reservation)->create();
        $this->resourceManagerUser = User::factory()->create();

        $resourceManagerDuty = Duty::factory()->has(Institution::factory()->state(
            ['padalinys_id' => $this->resourcePadalinys->id]
        ))->hasAttached($this->resourceManagerUser, ['start_date' => now()->subDays(1), 'end_date' => now()->addDays(1)])->create();

        $resourceManagerRole = Role::factory()->create();
        $resourceManagerRole->givePermissionTo(['reservations.read.*', config('permission.resource_managership_indicating_permission')]);

        $resourceManagerDuty->assignRole($resourceManagerRole);
    }

    public function test_simple_user_cannot_access_reservation()
    {
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
    }

    public function test_simple_user_cannot_update_reservation()
    {
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
    }

    public function test_simple_user_can_access_reservation_after_they_are_assigned_to_it()
    {
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
                ->where('flash.success', 'Rezervacijos valdytojai pridėti.')
            );

        $response = $this->actingAs($user)->get(route('reservations.show', $reservation->id));

        $this->followRedirects($response)->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.id', $reservation->id)
        );
    }

    public function test_simple_user_can_create_reservation()
    {
        $user = $this->simpleUser;

        $this->actingAs($user)->get(route('reservations.create'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/CreateReservation')
            ->whereNot('resources', null)
        );

        $reservation = Reservation::factory()->make([
            'name' => [
                'lt' => 'test',
            ],
            'resources' => $this->resources->map(fn ($resource) => ['id' => $resource->id, 'quantity' => 1])->toArray(),
        ]);

        $response = $this->actingAs($user)->post(route('reservations.store'),
            $reservation->toFullArray()
        );

        $response->assertStatus(302);

        $this->followRedirects($response)
            ->assertStatus(200)->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/ShowReservation')
            ->whereNot('reservation', null)
            ->where('reservation.name', $reservation->name)
            );
    }

    public function test_simple_user_can_update_reservation_resource_state_from_created_to_cancelled_after_they_are_assigned_to_it()
    {
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
        $this->assertEquals(Cancelled::class, get_class($resource->pivot->state));
    }

    public function test_resource_manager_can_update_reservation_resource_state_from_created_to_reserved()
    {
        $user = $this->resourceManagerUser;

        $this->actingAs($user)->get(route('reservations.show', $this->reservation->id));

        $resource = $this->reservation->resources->first();

        // assert that the resource is in created state
        $this->assertEquals(Created::class, get_class($resource->pivot->state));

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
        $this->assertEquals(Reserved::class, get_class($resource->pivot->state));
    }

    // reserved to lent
    public function test_resource_manager_can_update_reservation_resource_state_from_reserved_to_lent()
    {
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
        $this->assertEquals(Lent::class, get_class($resource->pivot->state));
    }

    // lent to returned
    public function test_resource_manager_can_update_reservation_resource_state_from_lent_to_returned()
    {
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
        $this->assertEquals(Returned::class, get_class($resource->pivot->state));
    }

    // created to rejected
    public function test_resource_manager_can_update_reservation_resource_state_from_created_to_rejected()
    {
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
        $this->assertEquals(Rejected::class, get_class($resource->pivot->state));
    }

    // created or reserved to cancelled, but this time, the simple user can do thi

    public function test_resource_manager_cannot_update_reservation_resource_state_that_has_already_been_cancelled()
    {
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
        $this->assertEquals(Cancelled::class, get_class($resource->pivot->state));
    }
}
