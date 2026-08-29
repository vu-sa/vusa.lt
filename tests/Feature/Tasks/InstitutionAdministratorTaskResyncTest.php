<?php

use App\Events\MeetingFullyCreated;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionAdministrator;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeUser($this->tenant);
    $this->admin->assignRole(config('permission.super_admin_role_name'));

    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->cadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);

    $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
        ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)]);

    $duty = Duty::factory()->for($this->institution)->hasAttached($studentRepType, [], 'types')->create();

    $this->member = User::factory()->create();
    $this->member->duties()->attach($duty, ['start_date' => '2019-01-01', 'end_date' => null]);

    $this->nominee = User::factory()->create();

    $this->meeting = Meeting::factory()->hasAttached($this->institution)->create([
        'start_time' => '2025-11-01 10:00:00',
    ]);
    AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);
    $this->meeting->load('agendaItems');

    event(new MeetingFullyCreated($this->meeting));

    $this->task = Task::query()
        ->where('taskable_type', MorphMap::alias(Meeting::class))
        ->where('taskable_id', $this->meeting->id)
        ->where('action_type', ActionType::AgendaCompletion)
        ->firstOrFail();
});

test('the open task starts with the members active at the meeting date', function (): void {
    expect($this->task->users()->pluck('users.id')->all())->toBe([$this->member->id]);
});

test('nominating an administrator takes the open task off the membership', function (): void {
    asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
        'cadence_id' => $this->cadence->id,
        'user_ids' => [$this->nominee->id],
    ])->assertRedirect();

    expect($this->task->fresh()->users()->pluck('users.id')->all())->toBe([$this->nominee->id]);
});

test('emptying the roster hands the open task back to the members', function (): void {
    InstitutionAdministrator::create([
        'institution_id' => $this->institution->id,
        'cadence_id' => $this->cadence->id,
        'user_id' => $this->nominee->id,
    ]);

    asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
        'cadence_id' => $this->cadence->id,
        'user_ids' => [],
    ])->assertRedirect();

    expect($this->task->fresh()->users()->pluck('users.id')->all())->toBe([$this->member->id]);
});

test('a completed task is left alone', function (): void {
    $this->task->update(['completed_at' => now()]);

    asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
        'cadence_id' => $this->cadence->id,
        'user_ids' => [$this->nominee->id],
    ])->assertRedirect();

    expect($this->task->fresh()->users()->pluck('users.id')->all())->toBe([$this->member->id]);
});

test('a meeting outside the edited term keeps its own assignees', function (): void {
    $oldMeeting = Meeting::factory()->hasAttached($this->institution)->create([
        'start_time' => '2021-03-01 10:00:00',
    ]);
    AgendaItem::factory()->create(['meeting_id' => $oldMeeting->id]);
    $oldMeeting->load('agendaItems');
    event(new MeetingFullyCreated($oldMeeting));

    $oldTask = Task::query()
        ->where('taskable_id', $oldMeeting->id)
        ->where('action_type', ActionType::AgendaCompletion)
        ->firstOrFail();

    asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
        'cadence_id' => $this->cadence->id,
        'user_ids' => [$this->nominee->id],
    ])->assertRedirect();

    expect($oldTask->fresh()->users()->pluck('users.id')->all())->toBe([$this->member->id]);
});

test('re-syncing sends nobody a task-assigned notification', function (): void {
    // Cutting mail down is the point of the feature; re-staffing a term must not
    // itself produce a burst of it.
    Notification::fake();

    asUser($this->admin)->put(route('institutions.administrators.update', $this->institution), [
        'cadence_id' => $this->cadence->id,
        'user_ids' => [$this->nominee->id],
    ])->assertRedirect();

    Notification::assertNotSentTo($this->nominee, TaskAssignedNotification::class);
});
