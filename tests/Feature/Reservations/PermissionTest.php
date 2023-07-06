<?php

namespace App\Tests\Feature\Reservations;

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
use Tests\TestCase;

class PermissionTest extends TestCase {

    use RefreshDatabase;

    public function test_simple_user_cannot_access_reservation() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create();

        $response = $this->actingAs($user)->get(route('reservations.show', $reservation->id));

        $response->assertStatus(403);
    }

    public function test_simple_user_cannot_update_reservation() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create();

        $response = $this->actingAs($user)->patch(route('reservations.update', $reservation->id), [
            ...$reservation->toArray(),
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(2),
        ]);

        $response->assertStatus(403);
    }

    public function test_reservation_user_can_update_reservation() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->recycle($user)->create();

        $response = $this->actingAs($user)->put(route('reservations.update', $reservation->id), [
            ...$reservation->toArray(),
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(2),
        ]);

        $response->assertStatus(200);
    }

    public function test_reservation_user_cannot_access_other_users_reservation() {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reservation = Reservation::factory()->recycle($user1)->create();

        $response = $this->actingAs($user2)->get('/reservations/' . $reservation->id);

        $response->assertStatus(403);
    }

    public function test_resource_manager_can_update_reservation_resource_state() {

        $padalinys = Padalinys::inRandomOrder()->first();

        $resource = Resource::factory()->for($padalinys)->create();

        $user = User::factory()->has(Reservation::factory()->hasAttached($resource))->create();

        $duty = Duty::factory()->has(Institution::factory()->state(
            ['padalinys_id' => $padalinys->id]
        ))->hasAttached($user, ['start_date' => now()->subDays(1), 'end_date' => now()->addDays(1)])->create();

        $resourceManagerRole = Role::factory()->create();
        $resourceManagerRole->givePermissionTo(['reservations.read.*', 'resources.update.padalinys']);

        $duty->assignRole($resourceManagerRole);

        $this->actingAs($user)->get(route('reservations.show', $user->reservations->first()->id));

        $response = $this->actingAs($user)->post(route("users.comments.store", $user->id), [
            'commentable_type' => 'reservation_resouce',
            'commentable_id' => $resource->reservations->first()->pivot->id,
            'comment' => 'test',
            'decision' => 'approve'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('reservations.show', $resource->reservations->first()->id));
        // assert status 200 after redirect
        $this->followRedirects($response)->assertStatus(200);
    }

    public function test_resource_manager_cannot_update_other_reservation_resource_pivots() {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reservation1 = Reservation::factory()->create();
        $reservation2 = Reservation::factory()->create();
        $resource = Resource::factory()->create();

        $user2->assignRole('resource_manager');

        $response = $this->actingAs($user2)->put('/reservations/' . $reservation2->id . '/resources/' . $resource->id, [
            'quantity' => 2,
        ]);

        $response->assertStatus(403);
    }
}
