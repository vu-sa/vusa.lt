<?php

/**
 * Tests for ApprovalTaskSubscriber.
 *
 * @see ApprovalTaskSubscriber
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
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskAutoCompletedNotification;
use App\States\ReservationResource\Created;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Subscribers\ApprovalTaskSubscriber;
use Database\Seeders\RoleCentralResourceManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('ApprovalTaskSubscriber', function (): void {
    describe('approval task creation', function (): void {
        test('does not create task when no approvers exist', function (): void {
            $tenant = Tenant::query()->first()
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
                ->where('taskable_type', MorphMap::alias(Reservation::class))
                ->where('taskable_id', $reservation->id)
                ->where('action_type', ActionType::Approval)
                ->count();

            // Task should not be created because no approvers exist
            expect($approvalTaskCount)->toBe(0);
        });
    });

    describe('notifications', function (): void {
        test('approvers are asked once: the approval request, not a task notice as well', function (): void {
            $tenant = Tenant::query()->first();
            $approver = makeTenantUserWithRole('Išteklių administratorius', $tenant);
            $resource = Resource::factory()->create(['tenant_id' => $tenant->id, 'resource_category_id' => ResourceCategory::factory()->create()->id]);

            $reservation = Reservation::factory()->create(['start_time' => now()->addDays(3), 'end_time' => now()->addDays(5)]);
            $reservation->users()->attach(User::factory()->create()->id);
            $reservation->resources()->attach($resource->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);
            $reservationResource = ReservationResource::query()->where('reservation_id', $reservation->id)->first();

            event(new ApprovalRequested($reservationResource, step: 1));

            Notification::assertSentTo($approver, ApprovalRequestedNotification::class);
            Notification::assertNotSentTo($approver, TaskAssignedNotification::class);
        });

        test('a central office manager holding only the global permission is asked about its own padalinys\' items only', function (): void {
            $centralOffice = Tenant::query()->first();
            $centralManager = makeTenantUserWithRole(RoleCentralResourceManagerSeeder::NAME, $centralOffice);

            $requestFor = function (Tenant $tenant): ReservationResource {
                $resource = Resource::factory()->create(['tenant_id' => $tenant->id, 'resource_category_id' => ResourceCategory::factory()->create()->id]);
                $reservation = Reservation::factory()->create(['start_time' => now()->addDays(3), 'end_time' => now()->addDays(5)]);
                $reservation->resources()->attach($resource->id, [
                    'quantity' => 1,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'state' => 'created',
                ]);

                return ReservationResource::query()->where('reservation_id', $reservation->id)->firstOrFail();
            };

            event(new ApprovalRequested($requestFor($centralOffice), step: 1));
            Notification::assertSentTo($centralManager, ApprovalRequestedNotification::class);

            // Approving elsewhere stays possible (wildcard), but other padaliniai's queues are not theirs to be pinged about.
            Notification::fake();
            event(new ApprovalRequested($requestFor(Tenant::factory()->create()), step: 1));
            Notification::assertNotSentTo($centralManager, ApprovalRequestedNotification::class);
        });

        test('the approver who decided is not told their own task completed', function (): void {
            $decider = User::factory()->create();
            $colleague = User::factory()->create();
            $resource = Resource::factory()->create(['tenant_id' => Tenant::query()->first()->id, 'resource_category_id' => ResourceCategory::factory()->create()->id]);
            $reservation = Reservation::factory()->create(['start_time' => now()->addDays(3), 'end_time' => now()->addDays(5)]);
            $reservation->resources()->attach($resource->id, [
                'quantity' => 1,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => 'created',
            ]);
            $reservationResource = ReservationResource::query()->where('reservation_id', $reservation->id)->first();

            $task = Task::factory()->create([
                'taskable_type' => MorphMap::alias(Reservation::class),
                'taskable_id' => $reservation->id,
                'action_type' => ActionType::Approval,
            ]);
            $task->users()->sync([$decider->id, $colleague->id]);

            $approval = Approval::factory()->create([
                'approvable_type' => $reservationResource->getMorphClass(),
                'approvable_id' => $reservationResource->id,
                'user_id' => $decider->id,
                'decision' => ApprovalDecision::Approved,
                'step' => 1,
            ]);

            event(new ApprovalDecisionMade($approval, $reservationResource));

            Notification::assertSentTo($colleague, TaskAutoCompletedNotification::class);
            Notification::assertNotSentTo($decider, TaskAutoCompletedNotification::class);
        });
    });

    describe('approval task completion', function (): void {
        test('completes approval task when ApprovalDecisionMade event is fired', function (): void {
            $tenant = Tenant::query()->first()
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
                'taskable_type' => MorphMap::alias(Reservation::class),
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

        test('completes multiple approval tasks for same reservation', function (): void {
            $tenant = Tenant::query()->first()
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
                'taskable_type' => MorphMap::alias(Reservation::class),
                'taskable_id' => $reservation->id,
                'action_type' => ActionType::Approval,
                'completed_at' => null,
            ]);

            $task2 = Task::factory()->create([
                'taskable_type' => MorphMap::alias(Reservation::class),
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
