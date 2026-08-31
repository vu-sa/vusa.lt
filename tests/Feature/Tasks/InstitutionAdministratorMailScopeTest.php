<?php

use App\Enums\AgendaItemType;
use App\Events\MeetingFullyCreated;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionAdministrator;
use App\Models\Meeting;
use App\Models\NotificationDigestQueue;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskAutoCompletedNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Nominating an administrator is meant to keep a sitting of a large body out of every
 * member's inbox. These assert the whole way through: assignment, the mail scheduled for
 * it, and the mail scheduled when it auto-completes.
 *
 * Notifications are deliberately NOT faked — "a mail is scheduled" means a row in
 * `notification_digest_queue`, which only QueueNotificationForDigest writes, off a real send.
 */
pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['queue.default' => 'sync']);

    $this->institution = Institution::factory()->for(Tenant::query()->first())->create();

    $this->cadence = Cadence::factory()->create([
        'institution_id' => $this->institution->id,
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
    ]);

    $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
        ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)]);

    $duty = Duty::factory()->for($this->institution)->hasAttached($studentRepType, [], 'types')->create();

    // Three people who hold a seat right now, and would all be mailed without a nomination.
    $this->members = User::factory()->count(3)->create();
    $this->members->each(fn (User $member) => $member->duties()->attach($duty, [
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => null,
    ]));

    $this->administrator = User::factory()->create();
});

function nominateAdministrator(Institution $institution, Cadence $cadence, User $user): void
{
    InstitutionAdministrator::create([
        'institution_id' => $institution->id,
        'cadence_id' => $cadence->id,
        'user_id' => $user->id,
    ]);
}

function meetingNeedingItsAgendaFilled(Institution $institution): Meeting
{
    $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()]);

    // Type null, so the item counts as unfilled and the task stays open.
    AgendaItem::factory()->count(2)->sequentialOrder()->create(['meeting_id' => $meeting->id]);

    $meeting->load('agendaItems');
    event(new MeetingFullyCreated($meeting));

    return $meeting;
}

function agendaCompletionTaskFor(Meeting $meeting): Task
{
    return Task::query()
        ->where('taskable_type', MorphMap::alias(Meeting::class))
        ->where('taskable_id', $meeting->id)
        ->where('action_type', ActionType::AgendaCompletion)
        ->firstOrFail();
}

/**
 * Fill every agenda item so the completion task auto-completes on the last save.
 */
function fillEveryAgendaItem(Meeting $meeting): void
{
    $meeting->agendaItems()->get()->each(function (AgendaItem $item): void {
        $item->type = AgendaItemType::Informational;
        $item->save();
    });
}

/**
 * The mail scheduled for one person, by notification class.
 *
 * @return array<int, string>
 */
function scheduledMailFor(User $user): array
{
    return NotificationDigestQueue::query()
        ->where('user_id', $user->id)
        ->pluck('notification_class')
        ->all();
}

test('the administrator carries the task alone and is the only one mailed about it', function (): void {
    nominateAdministrator($this->institution, $this->cadence, $this->administrator);

    $meeting = meetingNeedingItsAgendaFilled($this->institution);

    expect(agendaCompletionTaskFor($meeting)->users()->pluck('users.id')->all())
        ->toBe([$this->administrator->id]);

    expect(scheduledMailFor($this->administrator))->toContain(TaskAssignedNotification::class);

    $this->members->each(fn (User $member) => expect(scheduledMailFor($member))
        ->not->toContain(TaskAssignedNotification::class));
});

test('auto-completing the agenda mails the administrator, not the members', function (): void {
    nominateAdministrator($this->institution, $this->cadence, $this->administrator);

    $meeting = meetingNeedingItsAgendaFilled($this->institution);
    NotificationDigestQueue::query()->delete();

    fillEveryAgendaItem($meeting);

    expect(agendaCompletionTaskFor($meeting)->completed_at)->not->toBeNull();
    expect(scheduledMailFor($this->administrator))->toContain(TaskAutoCompletedNotification::class);

    $this->members->each(fn (User $member) => expect(scheduledMailFor($member))->toBeEmpty());
});

test('a task reopened after the nomination is re-staffed, and mails only the administrator', function (): void {
    // The production case: the task was assigned and completed while the body still
    // carried it collectively, so the nomination could not touch it — the re-sync leaves
    // completed tasks alone. Reopening it must not revive that roster.
    $meeting = meetingNeedingItsAgendaFilled($this->institution);
    $task = agendaCompletionTaskFor($meeting);

    expect($task->users()->pluck('users.id')->all())
        ->toEqualCanonicalizing($this->members->pluck('id')->all());

    fillEveryAgendaItem($meeting);
    expect($task->fresh()->completed_at)->not->toBeNull();

    nominateAdministrator($this->institution, $this->cadence, $this->administrator);
    expect($task->fresh()->users()->pluck('users.id')->all())
        ->toEqualCanonicalizing($this->members->pluck('id')->all());

    // A voting item with no vote behind it is unfilled again, which reopens the task.
    $reopener = $meeting->agendaItems()->first();
    $reopener->type = AgendaItemType::Voting;
    $reopener->save();

    expect($task->fresh()->completed_at)->toBeNull()
        ->and($task->fresh()->users()->pluck('users.id')->all())->toBe([$this->administrator->id]);

    NotificationDigestQueue::query()->delete();

    $reopener->type = AgendaItemType::Informational;
    $reopener->save();

    expect($task->fresh()->completed_at)->not->toBeNull();
    expect(scheduledMailFor($this->administrator))->toContain(TaskAutoCompletedNotification::class);

    $this->members->each(fn (User $member) => expect(scheduledMailFor($member))->toBeEmpty());
});

test('extending a term over a meeting hands its open task to the administrator', function (): void {
    nominateAdministrator($this->institution, $this->cadence, $this->administrator);

    // Held after the term ends, so it falls back to the membership.
    $meeting = Meeting::factory()->hasAttached($this->institution)->create([
        'start_time' => now()->addMonths(9),
    ]);
    AgendaItem::factory()->create(['meeting_id' => $meeting->id]);
    $meeting->load('agendaItems');
    event(new MeetingFullyCreated($meeting));

    $task = agendaCompletionTaskFor($meeting);
    expect($task->users()->pluck('users.id')->all())
        ->toEqualCanonicalizing($this->members->pluck('id')->all());

    $this->cadence->update(['end_date' => now()->addYear()->toDateString()]);

    expect($task->fresh()->users()->pluck('users.id')->all())->toBe([$this->administrator->id]);
});
