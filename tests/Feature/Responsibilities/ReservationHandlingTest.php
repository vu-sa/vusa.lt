<?php

use App\Actions\GetResourceManagers;
use App\Actions\Schedulable\TaskNotifier;
use App\Enums\ApprovalDecision;
use App\Enums\NotificationType;
use App\Enums\ResponsibilityPurpose;
use App\Events\ApprovalRequested;
use App\Jobs\ReconcileReservationReviews;
use App\Models\Approval;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResponsibilityGrant;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\TaskAutoCompletedNotification;
use App\Notifications\TaskReminderNotification;
use App\Services\ApprovalService;
use App\Services\Notifications\NotificationAudience;
use App\Services\Permissions\ResponsibilityPermissionGaps;
use App\Tasks\Enums\ActionType;
use App\Tasks\Synchronizers\ReservationReviewTaskSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Bus::fake([ReconcileReservationReviews::class]);
    Notification::fake();
    $this->tenant = Tenant::query()->first();
    $this->handler = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);
    $this->duty = $this->handler->current_duties()->first();
    grantPurpose($this->duty, ResponsibilityPurpose::ReservationHandling);
    $this->owner = makeUser($this->tenant);
    $this->reservation = Reservation::factory()->create();
    $this->reservation->users()->attach($this->owner);
    $this->resource = Resource::factory()->for($this->tenant)->create();
    $this->reservation->resources()->attach($this->resource, [
        'quantity' => 1, 'state' => 'created',
        'start_time' => $this->reservation->start_time, 'end_time' => $this->reservation->end_time,
    ]);
    $this->item = ReservationResource::query()->where('reservation_id', $this->reservation->id)->firstOrFail();
    $this->sync = app(ReservationReviewTaskSynchronizer::class);
});

function reservationReviewTask(ReservationResource $item): Task
{
    return Task::query()->where('action_type', ActionType::Approval)
        ->where('metadata->reservation_resource_id', $item->id)->whereNull('completed_at')->firstOrFail();
}

test('contacts and requests use the purpose, while decisions still require permissions', function (): void {
    $permissionOnly = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);
    $purposeOnly = makeUser($this->tenant);
    $purposeDuty = $purposeOnly->current_duties()->first();
    grantPurpose($purposeDuty, ResponsibilityPurpose::ReservationHandling);

    expect(GetResourceManagers::execute($this->resource)->pluck('id')->all())
        ->toContain($this->handler->id, $purposeOnly->id)->not->toContain($permissionOnly->id)
        ->and($this->item->canBeApprovedBy($purposeOnly, 1, ApprovalDecision::Approved))->toBeFalse()
        ->and($this->item->canBeApprovedBy($permissionOnly, 1, ApprovalDecision::Approved))->toBeTrue()
        ->and($purposeOnly->can('view', $this->reservation))->toBeFalse()
        ->and(app(ResponsibilityPermissionGaps::class)->missingFor($purposeDuty, ResponsibilityPurpose::ReservationHandling))
        ->toContain('resources.update.padalinys', 'reservations.read.padalinys')
        ->and(app(NotificationAudience::class)->canReceive($purposeOnly, NotificationType::ApprovalRequested))->toBeTrue()
        ->and(app(NotificationAudience::class)->canReceive($permissionOnly, NotificationType::ApprovalRequested))->toBeFalse();

    $this->sync->reconcile($this->reservation, false);
    expect(reservationReviewTask($this->item)->users->pluck('id')->all())->toContain($purposeOnly->id)->not->toContain($permissionOnly->id);
});

test('a handover changes pending work but preserves the historical decision actor and completed task', function (): void {
    $completed = Task::factory()->create([
        'taskable_type' => $this->reservation->getMorphClass(), 'taskable_id' => $this->reservation->id,
        'action_type' => ActionType::Approval, 'completed_at' => now()->subDay(),
    ]);
    $completed->users()->sync([$this->handler->id]);
    $historical = Approval::factory()->create([
        'approvable_type' => $this->item->getMorphClass(), 'approvable_id' => $this->item->id,
        'user_id' => $this->handler->id, 'decision' => ApprovalDecision::Approved, 'reverted_at' => now(),
    ]);
    $replacement = User::factory()->create();
    $this->handler->dutiables()->firstWhere('duty_id', $this->duty->id)->update(['end_date' => today()->subDay()]);
    $replacement->duties()->attach($this->duty, ['start_date' => today()]);

    Notification::fake();
    $this->sync->reconcileAll();
    expect(reservationReviewTask($this->item)->users->modelKeys())->toBe([$replacement->id])
        ->and($completed->fresh()->users->modelKeys())->toBe([$this->handler->id])
        ->and($historical->fresh()->user_id)->toBe($this->handler->id)
        ->and(GetResourceManagers::execute($this->resource)->pluck('id')->all())->toBe([$replacement->id]);
    Notification::assertSentToTimes($replacement, ApprovalRequestedNotification::class, 1);
    Notification::assertNotSentTo($this->handler, ApprovalRequestedNotification::class);
    $this->sync->reconcileAll();
    Notification::assertSentToTimes($replacement, ApprovalRequestedNotification::class, 1);
});

test('a decision made before the handover worker runs records current reviewers on completion', function (): void {
    $former = $this->handler;
    $former->dutiables()->firstWhere('duty_id', $this->duty->id)->update(['end_date' => today()->subDay()]);
    $replacement = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);
    grantPurpose($replacement->current_duties()->first(), ResponsibilityPurpose::ReservationHandling);
    $task = reservationReviewTask($this->item);
    Notification::fake();

    app(ApprovalService::class)->approve($this->item, $replacement, ApprovalDecision::Approved);

    expect($task->fresh()->users->modelKeys())->toBe([$replacement->id]);
    Notification::assertNotSentTo($former, TaskAutoCompletedNotification::class);
});

test('date boundaries hand work to the next holder after the inclusive last day', function (): void {
    $this->travelTo(today()->setTime(12, 0));
    $this->handler->dutiables()->firstWhere('duty_id', $this->duty->id)->update(['end_date' => today()]);
    $replacement = User::factory()->create();
    $replacement->duties()->attach($this->duty, ['start_date' => today()->addDay()]);
    $this->sync->reconcileAll(false);
    expect(reservationReviewTask($this->item)->users->modelKeys())->toBe([$this->handler->id]);
    $this->travel(1)->days();
    $this->sync->reconcileAll(false);
    expect(reservationReviewTask($this->item)->users->modelKeys())->toBe([$replacement->id]);
});

test('reminders reconcile a handover before choosing recipients', function (): void {
    reservationReviewTask($this->item)->update(['due_date' => today()->addDay()]);
    $this->handler->dutiables()->firstWhere('duty_id', $this->duty->id)->update(['end_date' => today()->subDay()]);
    $replacement = User::factory()->create();
    $replacement->duties()->attach($this->duty, ['start_date' => today()]);
    Notification::fake();

    TaskNotifier::notifyDaysLeft(1);

    Notification::assertSentTo($replacement, TaskReminderNotification::class);
    Notification::assertNotSentTo($this->handler, TaskReminderNotification::class);
});

test('responsibility and membership changes share one after-commit reconciliation', function (): void {
    $this->handler->dutiables()->firstWhere('duty_id', $this->duty->id)->update(['end_date' => today()]);
    Bus::assertDispatched(ReconcileReservationReviews::class, fn ($job): bool => $job->afterCommit === true);
    $this->duty->responsibilityGrants()->whereHas('role', fn ($role) => $role->where('purpose', ResponsibilityPurpose::ReservationHandling))->first()->delete();
    Bus::assertDispatched(ReconcileReservationReviews::class, fn ($job): bool => $job->afterCommit === true);
});

test('resource ownership changes transfer only its outstanding review', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherHandler = makeUser($otherTenant);
    grantPurpose($otherHandler->current_duties()->first(), ResponsibilityPurpose::ReservationHandling);
    $this->resource->update(['tenant_id' => $otherTenant->id]);
    Bus::assertDispatched(ReconcileReservationReviews::class);
    (new ReconcileReservationReviews)->handle($this->sync);

    expect(reservationReviewTask($this->item)->users->modelKeys())->toBe([$otherHandler->id])
        ->and(GetResourceManagers::execute($this->resource->fresh())->pluck('id')->all())->toBe([$otherHandler->id]);
});

test('requesting your own review does not notify you about your action', function (): void {
    $this->actingAs($this->handler);
    Notification::fake();
    event(new ApprovalRequested($this->item, 1));
    Notification::assertNotSentTo($this->handler, ApprovalRequestedNotification::class);
});

test('vacancy keeps an unassigned review which is picked up when the duty is staffed', function (): void {
    $this->handler->dutiables()->firstWhere('duty_id', $this->duty->id)->delete();
    $this->sync->reconcileAll(false);
    expect(reservationReviewTask($this->item)->users)->toBeEmpty();
    $replacement = User::factory()->create();
    $replacement->duties()->attach($this->duty, ['start_date' => today()]);
    $this->sync->reconcileAll(false);
    expect(reservationReviewTask($this->item)->users->modelKeys())->toBe([$replacement->id]);
});

test('deciding an item never completes another tenants review task', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherHandler = makeTenantUserWithRole('Išteklių administratorius', $otherTenant);
    grantPurpose($otherHandler->current_duties()->first(), ResponsibilityPurpose::ReservationHandling);
    $otherResource = Resource::factory()->for($otherTenant)->create();
    $this->reservation->resources()->attach($otherResource, ['quantity' => 1, 'state' => 'created']);
    $otherItem = ReservationResource::query()->where('resource_id', $otherResource->id)->firstOrFail();
    $otherTask = reservationReviewTask($otherItem);
    $task = reservationReviewTask($this->item);

    app(ApprovalService::class)->approve($this->item, $this->handler, ApprovalDecision::Approved);

    expect($task->fresh()->completed_at)->not->toBeNull()
        ->and($otherTask->fresh()->completed_at)->toBeNull()
        ->and($otherTask->fresh()->users->modelKeys())->toBe([$otherHandler->id]);
});

test('inherited own-tenant grants reach only the resources covered by that duty', function (): void {
    $type = DutyType::factory()->create();
    $child = DutyType::factory()->create(['parent_id' => $type->id]);
    $otherTenant = Tenant::factory()->create();
    $other = makeUser($otherTenant);
    $other->current_duties()->first()->types()->attach($child);
    ResponsibilityGrant::factory()->forDutyType($type)->purpose(ResponsibilityPurpose::ReservationHandling)->forOwnTenant()->create();
    $otherResource = Resource::factory()->for($otherTenant)->create();
    expect(GetResourceManagers::execute($otherResource)->pluck('id')->all())->toBe([$other->id])
        ->and(GetResourceManagers::execute($this->resource)->pluck('id')->all())->not->toContain($other->id);
});

test('trashed duties and institutions stop supplying contacts and restored ones resume', function (): void {
    $this->duty->delete();
    $this->sync->reconcileAll(false);
    expect(GetResourceManagers::execute($this->resource))->toBeEmpty()
        ->and(reservationReviewTask($this->item)->users)->toBeEmpty();
    $this->duty->restore();
    $this->duty->institution->delete();
    $this->sync->reconcileAll(false);
    expect(GetResourceManagers::execute($this->resource))->toBeEmpty();
    Institution::withTrashed()->find($this->duty->institution_id)->restore();
    $this->sync->reconcileAll(false);
    expect(GetResourceManagers::execute($this->resource)->pluck('id')->all())->toBe([$this->handler->id]);
});

test('the resource and reservation expose the same compact contacts', function (): void {
    asUserWithInertia($this->owner)->get(route('resources.show', $this->resource))->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('managers.0.id', $this->handler->id)->missing('managers.0.roles'));
    asUserWithInertia($this->owner)->get(route('reservations.show', $this->reservation))->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('reservation.resources.0.managers.0.id', $this->handler->id)->missing('reservation.resources.0.managers.0.roles'));
});
