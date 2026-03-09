<?php

use App\Enums\ApprovalDecision;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalFlowCompleted;
use App\Events\ApprovalRequested;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);

    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Resource Manager');

    $this->category = ResourceCategory::factory()->create();
    $this->resource = Resource::factory()->create([
        'tenant_id' => $this->tenant->id,
        'resource_category_id' => $this->category->id,
    ]);

    $this->reservation = Reservation::factory()->create([
        'start_time' => now()->addDays(1),
        'end_time' => now()->addDays(1)->addHours(2),
    ]);
    $this->reservation->users()->attach($this->user->id);
    $this->reservation->resources()->attach($this->resource->id, [
        'quantity' => 1,
        'start_time' => $this->reservation->start_time,
        'end_time' => $this->reservation->end_time,
        'state' => 'created',
    ]);

    $this->reservationResource = ReservationResource::query()
        ->where('reservation_id', $this->reservation->id)
        ->where('resource_id', $this->resource->id)
        ->first();

    $this->approvalService = app(ApprovalService::class);
});

describe('ApprovalService', function () {
    test('can request approval for a reservation resource', function () {
        Event::fake([ApprovalRequested::class]);

        $this->approvalService->requestApproval($this->reservationResource, 1);

        Event::assertDispatched(ApprovalRequested::class, function ($event) {
            return $event->approvable->id === $this->reservationResource->id;
        });
    });

    test('can approve a reservation resource', function () {
        Event::fake([ApprovalDecisionMade::class, ApprovalFlowCompleted::class]);

        $approval = $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Approved,
            'Approved for the event',
            1
        );

        expect($approval)->not->toBeNull();
        expect($approval->decision)->toBe(ApprovalDecision::Approved);
        expect($approval->user_id)->toBe($this->resourceManager->id);
        expect($approval->notes)->toBe('Approved for the event');

        Event::assertDispatched(ApprovalDecisionMade::class);
    });

    test('can reject a reservation resource', function () {
        Event::fake([ApprovalDecisionMade::class]);

        $approval = $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Rejected,
            'Resource not available',
            1
        );

        expect($approval->decision)->toBe(ApprovalDecision::Rejected);

        Event::assertDispatched(ApprovalDecisionMade::class);
    });

    test('can cancel a reservation resource', function () {
        Event::fake([ApprovalDecisionMade::class]);

        $approval = $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Cancelled,
            null,
            1
        );

        expect($approval->decision)->toBe(ApprovalDecision::Cancelled);
    });

    test('can bulk approve multiple reservation resources', function () {
        Event::fake([ApprovalDecisionMade::class]);

        // Create additional reservation resources
        $reservation2 = Reservation::factory()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);
        $reservation2->users()->attach($this->user->id);
        $reservation2->resources()->attach($this->resource->id, [
            'quantity' => 1,
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'state' => 'created',
        ]);

        $reservationResource2 = ReservationResource::query()
            ->where('reservation_id', $reservation2->id)
            ->where('resource_id', $this->resource->id)
            ->first();

        $results = $this->approvalService->bulkApprove(
            collect([$this->reservationResource, $reservationResource2]),
            $this->resourceManager,
            ApprovalDecision::Approved,
            null,
            1
        );

        expect($results)->toHaveCount(2);

        Event::assertDispatchedTimes(ApprovalDecisionMade::class, 2);
    });
});

describe('Task Auto-Completion', function () {
    test('completing approval marks related task as complete', function () {
        // Create an approval task
        $task = Task::factory()->create([
            'taskable_type' => 'reservation_resource',
            'taskable_id' => $this->reservationResource->id,
            'action_type' => 'approval',
            'completed_at' => null,
        ]);

        // Approve the reservation resource
        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Approved,
            null,
            1
        );

        $task->refresh();
        expect($task->completed_at)->not->toBeNull();
    });

    test('rejecting approval also marks related task as complete', function () {
        $task = Task::factory()->create([
            'taskable_type' => 'reservation_resource',
            'taskable_id' => $this->reservationResource->id,
            'action_type' => 'approval',
            'completed_at' => null,
        ]);

        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Rejected,
            null,
            1
        );

        $task->refresh();
        expect($task->completed_at)->not->toBeNull();
    });
});

describe('ReservationResource State Transitions', function () {
    test('approving created reservation resource transitions to reserved', function () {
        expect($this->reservationResource->state->getValue())->toBe('created');

        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Approved,
            null,
            1
        );

        $this->reservationResource->refresh();
        expect($this->reservationResource->state->getValue())->toBe('reserved');
    });

    test('approving reserved reservation resource transitions to lent', function () {
        // First transition to reserved
        $this->reservationResource->state = 'reserved';
        $this->reservationResource->save();

        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Approved,
            null,
            1
        );

        $this->reservationResource->refresh();
        expect($this->reservationResource->state->getValue())->toBe('lent');
    });

    test('approving lent reservation resource transitions to returned', function () {
        $this->reservationResource->state = 'lent';
        $this->reservationResource->save();

        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Approved,
            null,
            1
        );

        $this->reservationResource->refresh();
        expect($this->reservationResource->state->getValue())->toBe('returned');
    });

    test('rejecting created reservation resource transitions to rejected', function () {
        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Rejected,
            null,
            1
        );

        $this->reservationResource->refresh();
        expect($this->reservationResource->state->getValue())->toBe('rejected');
    });

    test('cancelling reservation resource transitions to cancelled', function () {
        $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Cancelled,
            null,
            1
        );

        $this->reservationResource->refresh();
        expect($this->reservationResource->state->getValue())->toBe('cancelled');
    });
});
