<?php

/**
 * Tests for ReservationTaskSubscriber.
 *
 * @see \App\Tasks\Subscribers\ReservationTaskSubscriber
 */

use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\States\ReservationResource\Created;
use App\States\ReservationResource\Lent;
use App\States\ReservationResource\Reserved;
use App\States\ReservationResource\Returned;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('ReservationTaskSubscriber', function () {
    describe('pickup task creation', function () {
        test('creates pickup task when resource transitions to Reserved state', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $user = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($user->id);
            $reservation->resources()->attach($resource->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);

            $reservationResource = ReservationResource::query()
                ->where('reservation_id', $reservation->id)
                ->where('resource_id', $resource->id)
                ->first();

            // Transition to Reserved state (triggers pickup task)
            $reservationResource->state->transitionTo(Reserved::class);

            $pickupTask = Task::query()
                ->where('taskable_type', Reservation::class)
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Pickup)
                ->first();

            expect($pickupTask)->not->toBeNull()
                ->and($pickupTask->users)->toHaveCount(1)
                ->and($pickupTask->completed_at)->toBeNull();
        });

        test('does not create duplicate pickup task for same reservation', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $user = User::factory()->create();
            $category = ResourceCategory::factory()->create();

            // Create two resources
            $resource1 = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);
            $resource2 = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($user->id);

            // Attach both resources
            $reservation->resources()->attach($resource1->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);
            $reservation->resources()->attach($resource2->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);

            $reservationResource1 = ReservationResource::query()
                ->where('reservation_id', $reservation->id)
                ->where('resource_id', $resource1->id)
                ->first();

            $reservationResource2 = ReservationResource::query()
                ->where('reservation_id', $reservation->id)
                ->where('resource_id', $resource2->id)
                ->first();

            // Transition both to Reserved
            $reservationResource1->state->transitionTo(Reserved::class);
            $reservationResource2->state->transitionTo(Reserved::class);

            $pickupTaskCount = Task::query()
                ->where('taskable_type', Reservation::class)
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Pickup)
                ->count();

            expect($pickupTaskCount)->toBe(1);
        });
    });

    describe('pickup task progress', function () {
        test('auto-completes pickup task when resource transitions to Lent', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $user = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
                'name' => 'Projector',
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($user->id);
            $reservation->resources()->attach($resource->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);

            $reservationResource = ReservationResource::query()
                ->where('reservation_id', $reservation->id)
                ->where('resource_id', $resource->id)
                ->first();

            // First transition to Reserved (creates pickup task)
            $reservationResource->state->transitionTo(Reserved::class);

            $pickupTask = Task::query()
                ->where('taskable_type', Reservation::class)
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Pickup)
                ->first();

            expect($pickupTask)->not->toBeNull()
                ->and($pickupTask->completed_at)->toBeNull();

            // Then transition to Lent (increments progress and completes task since only 1 item)
            $reservationResource->refresh();
            $reservationResource->state->transitionTo(Lent::class);

            $pickupTask->refresh();

            expect($pickupTask->completed_at)->not->toBeNull();
        });
    });

    describe('return task creation', function () {
        test('creates return task when resource transitions to Lent state', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $user = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($user->id);
            $reservation->resources()->attach($resource->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => Reserved::$name,
            ]);

            $reservationResource = ReservationResource::query()
                ->where('reservation_id', $reservation->id)
                ->where('resource_id', $resource->id)
                ->first();

            // Transition to Lent (triggers return task creation)
            $reservationResource->state->transitionTo(Lent::class);

            $returnTask = Task::query()
                ->where('taskable_type', Reservation::class)
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Return)
                ->first();

            expect($returnTask)->not->toBeNull()
                ->and($returnTask->completed_at)->toBeNull();
        });
    });

    describe('return task progress', function () {
        test('auto-completes return task when resource transitions through full flow', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $user = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
                'name' => 'Projector',
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->subDays(1),
                'end_time' => now()->addDays(1),
            ]);
            $reservation->users()->attach($user->id);
            $reservation->resources()->attach($resource->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);

            $reservationResource = ReservationResource::query()
                ->where('reservation_id', $reservation->id)
                ->where('resource_id', $resource->id)
                ->first();

            // Go through full flow: Created → Reserved → Lent (creates return task) → Returned
            $reservationResource->state->transitionTo(Reserved::class);
            $reservationResource->refresh();
            $reservationResource->state->transitionTo(Lent::class);

            $returnTask = Task::query()
                ->where('taskable_type', Reservation::class)
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Return)
                ->first();

            expect($returnTask)->not->toBeNull()
                ->and($returnTask->completed_at)->toBeNull();

            // Now transition to Returned (completes the return task)
            $reservationResource->refresh();
            $reservationResource->state->transitionTo(Returned::class);

            $returnTask->refresh();

            expect($returnTask->completed_at)->not->toBeNull();
        });
    });
});
