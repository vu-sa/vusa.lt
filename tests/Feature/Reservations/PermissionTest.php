<?php

namespace App\Tests\Feature\Reservations;

use App\Models\Pivots\ReservationResource as PivotsReservationResource;
use App\Models\Reservation;
use App\Models\ReservationResource;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase {

    use RefreshDatabase;

    public function test_simple_user_cannot_access_reservation() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create();

        $response = $this->actingAs($user)->get('/reservations/' . $reservation->id);

        $response->assertStatus(403);
    }

    public function test_simple_user_cannot_update_reservation() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create();

        $response = $this->actingAs($user)->put('/reservations/' . $reservation->id, [
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(2),
        ]);

        $response->assertStatus(403);
    }

    public function test_reservation_user_can_update_reservation() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/reservations/' . $reservation->id, [
            'start_time' => now()->addHours(1),
            'end_time' => now()->addHours(2),
        ]);

        $response->assertStatus(200);
    }

    public function test_reservation_user_cannot_access_other_users_reservation() {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reservation = Reservation::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get('/reservations/' . $reservation->id);

        $response->assertStatus(403);
    }

    public function test_resource_manager_can_update_reservation_resource_pivots() {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create();
        $resource = Resource::factory()->create();
        $reservationResource = PivotsReservationResource::factory()->create([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
        ]);

        $user->assignRole('resource_manager');

        $response = $this->actingAs($user)->put('/reservations/' . $reservation->id . '/resources/' . $resource->id, [
            'quantity' => 2,
        ]);

        $response->assertStatus(200);
    }

    public function test_resource_manager_cannot_update_other_reservation_resource_pivots() {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reservation1 = Reservation::factory()->create();
        $reservation2 = Reservation::factory()->create();
        $resource = Resource::factory()->create();
        $reservationResource = PivotsReservationResource::factory()->create([
            'reservation_id' => $reservation1->id,
            'resource_id' => $resource->id,
        ]);

        $user2->assignRole('resource_manager');

        $response = $this->actingAs($user2)->put('/reservations/' . $reservation2->id . '/resources/' . $resource->id, [
            'quantity' => 2,
        ]);

        $response->assertStatus(403);
    }
}
