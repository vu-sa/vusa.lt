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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);

    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Išteklių administratorius');

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

describe('ApprovalService backtracking authorization', function (): void {
    test('single and bulk backtracking propagate permission denials', function (bool $bulk): void {
        $approval = $this->approvalService->approve($this->reservationResource, $this->resourceManager, ApprovalDecision::Approved);

        expect(fn () => $bulk
            ? $this->approvalService->bulkBacktrack(collect([$this->reservationResource]), $this->user)
            : $this->approvalService->backtrack($this->reservationResource, $this->user)
        )->toThrow(AuthorizationException::class, __('reservations.messages.backtrack_forbidden'));

        expect($this->reservationResource->refresh()->state->getValue())->toBe('reserved')
            ->and($approval->refresh()->reverted_at)->toBeNull();
    })->with(['single' => false, 'bulk' => true]);

    test('bulk backtracking reports lifecycle errors without treating them as permission denials', function (): void {
        $result = $this->approvalService->bulkBacktrack(collect([$this->reservationResource]), $this->resourceManager);

        expect($result['approvals'])->toBeEmpty()
            ->and($result['errors'])->toBe([__('reservations.messages.backtrack_invalid_state')])
            ->and($this->reservationResource->refresh()->state->getValue())->toBe('created');
    });

    test('a lifecycle error does not roll back a permitted item in the same batch', function (): void {
        $approval = $this->approvalService->approve($this->reservationResource, $this->resourceManager, ApprovalDecision::Approved);
        $otherReservation = Reservation::factory()->create();
        $otherReservation->resources()->attach($this->resource->id, [
            'quantity' => 1,
            'start_time' => $otherReservation->start_time,
            'end_time' => $otherReservation->end_time,
            'state' => 'created',
        ]);
        $otherPivot = ReservationResource::query()->where('reservation_id', $otherReservation->id)->firstOrFail();

        $result = $this->approvalService->bulkBacktrack(collect([$this->reservationResource, $otherPivot]), $this->resourceManager);

        expect($result['approvals'])->toHaveCount(1)
            ->and($result['errors'])->toBe([__('reservations.messages.backtrack_invalid_state')])
            ->and($this->reservationResource->refresh()->state->getValue())->toBe('created')
            ->and($approval->refresh()->reverted_at)->not->toBeNull()
            ->and($otherPivot->refresh()->state->getValue())->toBe('created');
    });

    test('a late permission denial rolls back earlier backtracking writes', function (): void {
        $approval = $this->approvalService->approve($this->reservationResource, $this->resourceManager, ApprovalDecision::Approved);
        $otherReservation = Reservation::factory()->create();
        $otherReservation->resources()->attach($this->resource->id, [
            'quantity' => 1,
            'start_time' => $otherReservation->start_time,
            'end_time' => $otherReservation->end_time,
            'state' => 'created',
        ]);
        $otherPivot = ReservationResource::query()->where('reservation_id', $otherReservation->id)->firstOrFail();
        $realService = $this->approvalService;
        $service = $this->partialMock(ApprovalService::class);
        $service->shouldReceive('backtrack')->twice()->andReturnUsing(function (ReservationResource $pivot, $user, $notes) use ($otherPivot, $realService) {
            if ($pivot->id === $otherPivot->id) {
                throw new AuthorizationException;
            }

            return $realService->backtrack($pivot, $user, $notes);
        });

        expect(fn () => $service->bulkBacktrack(collect([$this->reservationResource, $otherPivot]), $this->resourceManager))
            ->toThrow(AuthorizationException::class);

        expect($this->reservationResource->refresh()->state->getValue())->toBe('reserved')
            ->and($approval->refresh()->reverted_at)->toBeNull()
            ->and($approval->reverted_by_id)->toBeNull()
            ->and($approval->reversion_notes)->toBeNull();
    });
});

describe('ApprovalService', function (): void {
    test('can request approval for a reservation resource', function (): void {
        Event::fake([ApprovalRequested::class]);

        $this->approvalService->requestApproval($this->reservationResource, 1);

        Event::assertDispatched(ApprovalRequested::class, fn ($event) => $event->approvable->id === $this->reservationResource->id);
    });

    test('can approve a reservation resource', function (): void {
        Event::fake([ApprovalDecisionMade::class, ApprovalFlowCompleted::class]);

        $approval = $this->approvalService->approve(
            $this->reservationResource,
            $this->resourceManager,
            ApprovalDecision::Approved,
            'Approved for the event',
            1
        );

        expect($approval)->not->toBeNull()
            ->and($approval->decision)->toBe(ApprovalDecision::Approved)
            ->and($approval->user_id)->toBe($this->resourceManager->id)
            ->and($approval->notes)->toBe('Approved for the event');

        Event::assertDispatched(ApprovalDecisionMade::class);
    });

    test('can reject a reservation resource', function (): void {
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

    test('can cancel a reservation resource', function (): void {
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

    test('can bulk approve multiple reservation resources', function (): void {
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

describe('Task Auto-Completion', function (): void {
    test('completing approval marks related task as complete', function (): void {
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

    test('rejecting approval also marks related task as complete', function (): void {
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

describe('ReservationResource State Transitions', function (): void {
    test('approving created reservation resource transitions to reserved', function (): void {
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

    test('approving reserved reservation resource transitions to lent', function (): void {
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

    test('approving lent reservation resource transitions to returned', function (): void {
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

    test('rejecting created reservation resource transitions to rejected', function (): void {
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

    test('cancelling reservation resource transitions to cancelled', function (): void {
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

test('approval reloads a stale reservation resource before checking its state', function (): void {
    $stale = $this->reservationResource;
    ReservationResource::query()->whereKey($stale->id)->update(['state' => 'cancelled']);

    expect(fn () => $this->approvalService->approve($stale, $this->resourceManager, ApprovalDecision::Approved))
        ->toThrow(InvalidArgumentException::class);
    expect($stale->approvals()->count())->toBe(0);
    expect((string) $stale->fresh()->state)->toBe('cancelled');
});

test('a failed approval completion rolls back both the decision and state', function (): void {
    Event::listen(ApprovalFlowCompleted::class, fn () => throw new RuntimeException('Completion failed'));
    expect(fn () => $this->approvalService->approve($this->reservationResource, $this->resourceManager, ApprovalDecision::Approved))
        ->toThrow(RuntimeException::class, 'Completion failed');
    expect($this->reservationResource->approvals()->count())->toBe(0);
    expect((string) $this->reservationResource->fresh()->state)->toBe('created');
});

test('partial approval cannot change quantity before authorization', function (): void {
    $this->reservationResource->update(['quantity' => 3]);
    expect(fn () => $this->approvalService->approve(
        $this->reservationResource, $this->user, ApprovalDecision::Approved, approvedQuantity: 1,
    ))->toThrow(InvalidArgumentException::class);
    expect($this->reservationResource->fresh()->quantity)->toBe(3);
});
