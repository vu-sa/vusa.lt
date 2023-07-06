<?php

namespace Tests\Feature\Reservations;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Pivots\ReservationResource as PivotsReservationResource;
use App\Models\Reservation;
use App\Models\ReservationResource;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\TestCase;

#[CoversNothing]
class PermissionTest extends TestCase {

    use RefreshDatabase;

    private $simpleUser;
    private $reservationManagerUser;
    private $resourceManagerUser;
    private $reservation;
    private $resources;

    public function setUp(): void {
        parent::setUp();

        $padalinys = Padalinys::inRandomOrder()->first();

        $this->resources = Resource::factory()->for($padalinys)->count(3)->create();
        $this->reservation = Reservation::factory()->hasAttached($this->resources)->create();

        $this->simpleUser = User::factory()->create();
        $this->reservationManagerUser = User::factory()->hasAttached($this->reservation)->create();
        $this->resourceManagerUser = User::factory()->create();

        $resourceManagerDuty = Duty::factory()->has(Institution::factory()->state(
            ['padalinys_id' => $padalinys->id]
        ))->hasAttached($this->resourceManagerUser, ['start_date' => now()->subDays(1), 'end_date' => now()->addDays(1)])->create();

        $resourceManagerRole = Role::factory()->create();
        $resourceManagerRole->givePermissionTo(['reservations.read.*', config('permission.resource_managership_indicating_permission')]);

        $resourceManagerDuty->assignRole($resourceManagerRole);
    }

    public function test_simple_user_cannot_access_reservation() {
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

    public function test_simple_user_cannot_update_reservation() {

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

    public function test_simple_user_can_access_reservation_after_they_are_assigned_to_it() {
        $user = $this->simpleUser;
        $reservation = $this->reservation;

        // first assign the user to the reservation
        $this->actingAs($this->reservationManagerUser)->get(route('dashboard'));

        $response = $this->actingAs($this->reservationManagerUser)->put(route('reservations.add-users', $reservation->id), [
            'users' => [$user->id]
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

    public function test_simple_user_can_create_reservation() {
        $user = $this->simpleUser;

        $this->actingAs($user)->get(route('reservations.create'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reservations/CreateReservation')
            ->whereNot('resources', null)
        );

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'name' => [
                'lt' => 'test',
            ],
            'start_time' => now()->addDays(1)->timestamp,
            'end_time' => now()->addDays(2)->timestamp,
            'resources' => $this->resources->map(fn ($resource) => ['id' => $resource->id, 'quantity' => 1])->toArray()
        ]);

        $response->assertStatus(302)->assertRedirectToRoute('reservations.index');

        $reservation = Reservation::where('name->lt', 'test')->first();
        $this->assertNotNull($reservation);

        $response->assertStatus(302)->assertRedirectToRoute('reservations.index');
    }

    public function test_resource_manager_can_update_reservation_resource_state() {

        $user = $this->resourceManagerUser;

        $this->actingAs($user)->get(route('reservations.show', $this->reservation->id));

        $response = $this->post(route("users.comments.store", $user->id), [
            'commentable_type' => 'reservation_resouce',
            'commentable_id' => $this->reservation->resources->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve'
        ]);

        $response->assertStatus(302)->assertRedirect(route('reservations.show', $this->reservation->id));
        $this->followRedirects($response)->assertStatus(200);
    }
}
