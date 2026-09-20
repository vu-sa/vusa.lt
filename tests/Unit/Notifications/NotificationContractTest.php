<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Enums\NotificationUrgency;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\Dutiable;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\AssignedToResourceNotification;
use App\Notifications\BaseNotification;
use App\Notifications\CommentPostedNotification;
use App\Notifications\DutyExpiringNotification;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\MeetingReminderNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCompletedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use App\Notifications\WelcomeNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;

pest()->use(RefreshDatabase::class);

/**
 * @param  array<int, array{label: string, value: string}>  $rows
 */
function expectContextRows(array $rows): void
{
    expect($rows)->not->toBeEmpty()->and(count($rows))->toBeLessThanOrEqual(4);

    foreach ($rows as $row) {
        expect($row)->toHaveKeys(['label', 'value'])
            ->and($row['label'])->toBeString()->not->toBe('')->not->toStartWith('notifications.context.')
            ->and($row['value'])->toBeString()->not->toBe('');
    }
}

describe('base contract', function (): void {
    test('a report-only notification has no actions and no context', function (): void {
        $notification = new WelcomeNotification;

        expect($notification->primaryAction())->toBeNull()
            ->and($notification->secondaryAction())->toBeNull()
            ->and($notification->actions())->toBe([])
            ->and($notification->context(User::factory()->create()))->toBe([]);
    });

    test('toArray carries the contract and keeps the derived actions list', function (): void {
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification(Task::factory()->create());

        $data = $notification->toArray($user);

        expect($data)->toHaveKeys(['primaryAction', 'secondaryAction', 'context', 'actions'])
            ->and($data['primaryAction'])->toBe($notification->primaryAction())
            ->and($data['secondaryAction'])->toBeNull()
            ->and($data['actions'])->toBe([$notification->primaryAction()]);
    });

    test('toDigestItem carries context and the primary action', function (): void {
        $user = User::factory()->create();
        $notification = new TaskReminderNotification(Task::factory()->create(['due_date' => now()->addDays(3)]), 3);

        expect($notification->toDigestItem($user))
            ->toHaveKeys(['context', 'primaryAction'])
            ->and($notification->toDigestItem($user)['primaryAction'])->toBe($notification->primaryAction());
    });

    test('the mail button uses the primary action', function (): void {
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification(Task::factory()->create());

        $mail = $notification->toMail($user);

        expect($mail->actionText)->toBe($notification->primaryAction()['label'])
            ->and($mail->actionUrl)->toBe($notification->primaryAction()['url']);
    });

    test('the only two-action notification is the register-meeting / report-activity pair', function (): void {
        $task = Task::factory()->create(['metadata' => ['activity_status' => 'overdue']]);
        $notification = new InstitutionActivityNotification($task, Institution::factory()->create());

        expect($notification->primaryAction())->toHaveKeys(['label', 'url'])
            ->and($notification->secondaryAction())->toHaveKeys(['label', 'url'])
            ->and($notification->actions())->toHaveCount(2);
    });

    test('no notification overrides actions() any more', function (): void {
        $overriding = collect(glob(app_path('Notifications/*Notification.php')))
            ->map(fn (string $file): string => 'App\\Notifications\\'.basename($file, '.php'))
            ->filter(fn (string $class): bool => is_subclass_of($class, BaseNotification::class))
            ->filter(fn (string $class): bool => (new ReflectionMethod($class, 'actions'))->getDeclaringClass()->getName() !== BaseNotification::class)
            ->values()
            ->all();

        expect($overriding)->toBe([]);
    });
});

describe('act-tier context rows', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    test('TaskAssigned lists the institution and deadline', function (): void {
        $institution = Institution::factory()->create();
        $task = Task::factory()->create([
            'taskable_type' => $institution->getMorphClass(),
            'taskable_id' => $institution->id,
            'due_date' => now()->addDays(2),
        ]);

        $rows = (new TaskAssignedNotification($task))->context($this->user);

        expectContextRows($rows);
        expect(collect($rows)->pluck('value'))->toContain($institution->name, $task->due_date->format('Y-m-d'));
    });

    test('a blank value is dropped rather than rendered empty', function (): void {
        $task = Task::factory()->create(['due_date' => null]);

        $rows = (new TaskReminderNotification($task, 1))->context($this->user);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['value'])->toBe($task->name);
    });

    test('TaskOverdue reports the oldest deadline and how late it is', function (): void {
        $tasks = collect([
            Task::factory()->create(['due_date' => now()->subDays(3)]),
            Task::factory()->create(['due_date' => now()->subDays(10)]),
        ]);

        $rows = (new TaskOverdueNotification($tasks))->context($this->user);

        expectContextRows($rows);
        expect(collect($rows)->pluck('value'))->toContain(now()->subDays(10)->format('Y-m-d'));
    });

    test('MeetingReminder lists institution, time and format', function (): void {
        $institution = Institution::factory()->create();
        $meeting = Meeting::factory()->create(['type' => 'remote']);
        $meeting->institutions()->attach($institution);

        $rows = (new MeetingReminderNotification($meeting->refresh(), 24))->context($this->user);

        expectContextRows($rows);
        expect(collect($rows)->pluck('value'))->toContain($institution->name, $meeting->start_time->format('Y-m-d H:i'));
    });

    test('InstitutionActivity lists the institution and days without activity', function (): void {
        $institution = Institution::factory()->create();
        $task = Task::factory()->create(['metadata' => ['activity_status' => 'overdue', 'effective_days_since_activity' => 45]]);

        $rows = (new InstitutionActivityNotification($task, $institution))->context($this->user);

        expectContextRows($rows);
        expect(collect($rows)->pluck('value'))->toContain($institution->name, '45 d.');
    });

    test('ApprovalRequested only shows the step after the first', function (): void {
        $task = Task::factory()->create();

        expect((new ApprovalRequestedNotification($task, 1))->context($this->user))->toHaveCount(1)
            ->and((new ApprovalRequestedNotification($task, 2))->context($this->user))->toHaveCount(2);
    });

    test('AssignedToResource lists the object and who assigned it', function (): void {
        $notification = new AssignedToResourceNotification(
            ['modelClass' => 'User', 'name' => 'Ona Onaitė'],
            ['modelClass' => 'Reservation', 'name' => 'Salė 101', 'url' => '/r/1'],
        );

        $rows = $notification->context($this->user);

        expectContextRows($rows);
        expect(collect($rows)->pluck('value')->all())->toBe(['Salė 101', 'Ona Onaitė']);
    });

    test('DutyExpiring lists the duty, institution and end date', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create(['duty_id' => $duty->id, 'end_date' => now()->addDays(30)]);

        $rows = (new DutyExpiringNotification($duty, $dutiable, 30))->context($this->user);

        expectContextRows($rows);
        expect(collect($rows)->pluck('value'))->toContain($duty->name, now()->addDays(30)->format('Y-m-d'));
    });

    test('CommentPosted lists the object and the author', function (): void {
        $notification = new CommentPostedNotification(
            'Sveiki',
            ['modelClass' => 'Task', 'name' => 'Užduotis X', 'url' => '/t/1', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Jonas Jonaitis'],
        );

        expect(collect($notification->context($this->user))->pluck('value')->all())
            ->toBe(['Užduotis X', 'Jonas Jonaitis']);
    });
});

describe('channel policy', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    test('an act-tier notification is in-app, push and immediate email', function (): void {
        $notification = new TaskReminderNotification(Task::factory()->create(['due_date' => now()->addDays(3)]), 3);

        expect($notification->via($this->user))->toBe(['database', 'broadcast', WebPushChannel::class, 'mail']);
    });

    test('a know or record notification is in-app only: no push, no instant email', function (): void {
        $notification = new TaskCompletedNotification(Task::factory()->create(), $this->user);

        expect($notification->urgency())->toBe(NotificationUrgency::Record)
            ->and($notification->via($this->user))->toBe(['database', 'broadcast'])
            ->and($notification->supportsEmailDigest())->toBeTrue();
    });

    test('an act notification the tier says should not push still emails', function (): void {
        $notification = new AssignedToResourceNotification(
            ['modelClass' => 'User', 'name' => 'Ona'],
            ['modelClass' => 'Reservation', 'name' => 'Salė', 'url' => '/r/1'],
        );

        expect($notification->via($this->user))->toBe(['database', 'broadcast', 'mail']);
    });

    test('the email toggle governs instant mail, and the push toggle governs push', function (): void {
        $notification = new TaskReminderNotification(Task::factory()->create(['due_date' => now()->addDays(3)]), 3);

        $this->user->setNotificationPreference(NotificationCategory::Task, NotificationChannel::EmailDigest, false);
        expect($notification->via($this->user))->toBe(['database', 'broadcast', WebPushChannel::class]);

        $this->user->setNotificationPreference(NotificationCategory::Task, NotificationChannel::Push, false);
        expect($notification->via($this->user))->toBe(['database', 'broadcast']);
    });

    test('a globally muted user gets nothing', function (): void {
        $this->user->muteNotificationsUntil(now()->addHour());
        $notification = new TaskReminderNotification(Task::factory()->create(), 3);

        expect($notification->via($this->user))->toBe([]);
    });

    test('a task assigned with a deadline inside a week is urgent, otherwise only worth knowing', function (?int $days, NotificationUrgency $expected): void {
        $task = Task::factory()->create(['due_date' => $days === null ? null : now()->addDays($days)]);

        expect((new TaskAssignedNotification($task))->urgency())->toBe($expected);
    })->with([
        'due in 3 days' => [3, NotificationUrgency::Act],
        'due in 30 days' => [30, NotificationUrgency::Know],
        'no deadline' => [null, NotificationUrgency::Know],
    ]);

    test('a mention asks for a reply, thread activity is only news', function (): void {
        $object = ['modelClass' => 'Task', 'name' => 'Užduotis', 'url' => '/t/1', 'id' => '1'];
        $author = ['modelClass' => 'User', 'name' => 'Jonas'];

        expect((new CommentPostedNotification('x', $object, $author, isMention: true))->urgency())->toBe(NotificationUrgency::Act)
            ->and((new CommentPostedNotification('x', $object, $author))->urgency())->toBe(NotificationUrgency::Know);
    });

    test('a welcome greeting stays in the app', function (): void {
        $notification = new WelcomeNotification;

        expect($notification->via($this->user))->toBe(['database', 'broadcast'])
            ->and($notification->supportsEmailDigest())->toBeFalse();
    });

    test('push is held until 07:00 during quiet hours and nothing else is delayed', function (): void {
        $notification = new TaskReminderNotification(Task::factory()->create(), 3);

        $this->travelTo(CarbonImmutable::parse('2026-09-20 23:30:00', 'Europe/Vilnius'));
        expect($notification->withDelay($this->user, WebPushChannel::class)->format('Y-m-d H:i'))->toBe('2026-09-21 07:00')
            ->and($notification->withDelay($this->user, 'mail'))->toBeNull()
            ->and($notification->withDelay($this->user, 'database'))->toBeNull();

        $this->travelTo(CarbonImmutable::parse('2026-09-21 12:00:00', 'Europe/Vilnius'));
        expect($notification->withDelay($this->user, WebPushChannel::class))->toBeNull();
    });

    test('the push carries the title and lands on the primary action', function (): void {
        $notification = new TaskAssignedNotification(Task::factory()->create());

        $push = $notification->toWebPush($this->user, null)->toArray();

        expect($push['title'])->toBe($notification->title($this->user))
            ->and($push['data']['url'])->toBe($notification->primaryAction()['url'])
            ->and($push['actions'][0]['title'])->toBe($notification->primaryAction()['label']);
    });
});
