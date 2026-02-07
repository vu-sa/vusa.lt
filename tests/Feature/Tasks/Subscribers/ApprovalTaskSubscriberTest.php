<?php

/**
 * Tests for ApprovalTaskSubscriber.
 *
 * @see \App\Tasks\Subscribers\ApprovalTaskSubscriber
 */

use App\Enums\ApprovalDecision;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalRequested;
use App\Models\Approval;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\States\ReservationResource\Created;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('ApprovalTaskSubscriber', function () {
    describe('approval task creation', function () {
        test('does not create task when no approvers exist', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $requester = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($requester->id);
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

            // Fire event - no approvers configured
            event(new ApprovalRequested($reservationResource, step: 1));

            $approvalTaskCount = Task::query()
                ->where('taskable_type', Reservation::class)
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Approval)
                ->count();

            // Task should not be created because no approvers exist
            expect($approvalTaskCount)->toBe(0);
        });
    });

    describe('approval task completion', function () {
        test('completes approval task when ApprovalDecisionMade event is fired', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $requester = User::factory()->create();
            $approver = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($requester->id);
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

            // Create an approval task manually
            $approvalTask = Task::factory()->create([
                'taskable_type' => Reservation::class,
                'taskable_id' => $reservation->id,
                'action_type' => ActionType::Approval,
                'completed_at' => null,
            ]);

            expect($approvalTask->completed_at)->toBeNull();

            // Create an approval record and fire the event
            $approval = Approval::factory()->create([
                'approvable_type' => $reservationResource->getMorphClass(),
                'approvable_id' => $reservationResource->id,
                'user_id' => $approver->id,
                'decision' => ApprovalDecision::Approved,
                'step' => 1,
            ]);

            event(new ApprovalDecisionMade($approval, $reservationResource));

            $approvalTask->refresh();

            expect($approvalTask->completed_at)->not->toBeNull();
        });

        test('completes multiple approval tasks for same reservation', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $requester = User::factory()->create();
            $approver = User::factory()->create();
            $category = ResourceCategory::factory()->create();
            $resource = Resource::factory()->create([
                'tenant_id' => $tenant->id,
                'resource_category_id' => $category->id,
            ]);

            $reservation = Reservation::factory()->create([
                'start_time' => now()->addDays(3),
                'end_time' => now()->addDays(5),
            ]);
            $reservation->users()->attach($requester->id);
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

            // Create two approval tasks (simulating multi-step approval)
            $task1 = Task::factory()->create([
                'taskable_type' => Reservation::class,
                'taskable_id' => $reservation->id,
                'action_type' => ActionType::Approval,
                'completed_at' => null,
            ]);

            $task2 = Task::factory()->create([
                'taskable_type' => Reservation::class,
                'taskable_id' => $reservation->id,
                'action_type' => ActionType::Approval,
                'completed_at' => null,
            ]);

            // Create an approval record and fire the event
            $approval = Approval::factory()->create([
                'approvable_type' => $reservationResource->getMorphClass(),
                'approvable_id' => $reservationResource->id,
                'user_id' => $approver->id,
                'decision' => ApprovalDecision::Approved,
                'step' => 1,
            ]);

            event(new ApprovalDecisionMade($approval, $reservationResource));

            $task1->refresh();
            $task2->refresh();

            expect($task1->completed_at)->not->toBeNull()
                ->and($task2->completed_at)->not->toBeNull();
        });
    });
});
