<?php

use App\Enums\NotificationCategory;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\Dutiable;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AssignedToResourceNotification;
use App\Notifications\DutyExpiringNotification;
use App\Notifications\MeetingReminderNotification;
use App\Notifications\MemberRegistrationNotification;
use App\Notifications\StudentRepRegistrationNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCompletedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use App\Notifications\TestPushNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;

pest()->use(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| TaskAssignedNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskAssignedNotification', function (): void {
    test('has correct category', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns correct title', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->title($user))->toBeString();
    });

    test('returns body with assigner when provided', function (): void {
        $task = Task::factory()->create();
        $assigner = User::factory()->create(['name' => 'Test Assigner']);
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task, $assigner);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns body without assigner when not provided', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->url())->toBe(route('userTasks'));
    });

    test('returns TASK as modelClass', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->modelClass())->toBe('TASK');
    });

    test('returns subject when assigner is provided', function (): void {
        $task = Task::factory()->create();
        $assigner = User::factory()->create(['name' => 'Test Assigner']);
        $notification = new TaskAssignedNotification($task, $assigner);

        $subject = $notification->subject();
        expect($subject)->toBeArray()
            ->toMatchArray(['modelClass' => 'User', 'name' => 'Test Assigner']);
    });

    test('returns null subject when assigner is not provided', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->subject())->toBeNull();
    });

    test('returns correct object structure', function (): void {
        $task = Task::factory()->create(['name' => 'Test Task']);
        $notification = new TaskAssignedNotification($task);

        $object = $notification->object();
        expect($object)->toBeArray()
            ->toMatchArray(['modelClass' => 'Task', 'name' => 'Test Task', 'id' => $task->id]);
    });

    test('has action buttons', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        $actions = $notification->actions();
        expect($actions)->toBeArray()->not->toBeEmpty();
    });

    test('supports email digest by default', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->supportsEmailDigest())->toBeTrue();
    });

    test('uses default via channels without mail', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task);

        $channels = $notification->via($user);
        expect($channels)->toContain('database')
            ->toContain('broadcast')
            ->toContain(WebPushChannel::class)->not->toContain('mail');
    });
});

/*
|--------------------------------------------------------------------------
| TaskCompletedNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskCompletedNotification', function (): void {
    test('has correct category', function (): void {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns correct title', function (): void {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions task name and completer', function (): void {
        $task = Task::factory()->create(['name' => 'Important Task']);
        $completedBy = User::factory()->create(['name' => 'Completer User']);
        $user = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url', function (): void {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->url())->toBe(route('userTasks'));
    });

    test('uses checkmark icon', function (): void {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->icon())->toBe('✅');
    });

    test('returns subject with completer info', function (): void {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create(['name' => 'Completer']);
        $notification = new TaskCompletedNotification($task, $completedBy);

        $subject = $notification->subject();
        expect($subject)->toMatchArray(['name' => 'Completer', 'modelClass' => 'User']);
    });

    test('supports email digest', function (): void {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->supportsEmailDigest())->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| TaskReminderNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskReminderNotification', function (): void {
    test('has correct category', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns correct title with days left', function (): void {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions task name and days left', function (): void {
        $task = Task::factory()->create(['name' => 'Important Task']);
        $user = User::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('uses warning icon when 1 day or less', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 1);

        expect($notification->icon())->toBe('⚠️');
    });

    test('uses alarm icon when more than 1 day', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->icon())->toBe('⏰');
    });

    test('does not support email digest (time-sensitive)', function (): void {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('returns correct object structure', function (): void {
        $task = Task::factory()->create(['name' => 'Test Task']);
        $notification = new TaskReminderNotification($task, 3);

        $object = $notification->object();
        expect($object)->toMatchArray(['modelClass' => 'Task', 'name' => 'Test Task']);
    });
});

/*
|--------------------------------------------------------------------------
| TaskOverdueNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskOverdueNotification', function (): void {
    test('has correct category', function (): void {
        $tasks = collect([Task::factory()->create()]);
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns title with task count', function (): void {
        $tasks = collect([
            Task::factory()->create(),
            Task::factory()->create(),
        ]);
        $user = User::factory()->create();
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->title($user))->toBeString();
    });

    test('body for single task mentions task name', function (): void {
        $task = Task::factory()->create(['name' => 'Single Overdue Task']);
        $tasks = collect([$task]);
        $user = User::factory()->create();
        $notification = new TaskOverdueNotification($tasks);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('body for multiple tasks mentions count', function (): void {
        $tasks = collect([
            Task::factory()->create(['name' => 'Task 1']),
            Task::factory()->create(['name' => 'Task 2']),
            Task::factory()->create(['name' => 'Task 3']),
        ]);
        $user = User::factory()->create();
        $notification = new TaskOverdueNotification($tasks);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('uses warning icon', function (): void {
        $tasks = collect([Task::factory()->create()]);
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->icon())->toBe('⚠️');
    });

    test('does not support email digest (important)', function (): void {
        $tasks = collect([Task::factory()->create()]);
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| DutyExpiringNotification Tests
|--------------------------------------------------------------------------
*/

describe('DutyExpiringNotification', function (): void {
    test('has correct category', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->category())->toBe(NotificationCategory::Duty);
    });

    test('returns correct title with days', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $user = User::factory()->create();
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions duty name and date', function (): void {
        $duty = Duty::factory()->create(['name' => 'Test Duty']);
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $user = User::factory()->create();
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url to duty', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->url())->toBe(route('duties.show', $duty->id));
    });

    test('uses bell icon', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->icon())->toBe('🔔');
    });

    test('returns DUTY as modelClass', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->modelClass())->toBe('DUTY');
    });

    test('does not support email digest', function (): void {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('returns correct object structure', function (): void {
        $duty = Duty::factory()->create(['name' => 'Test Duty']);
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        $object = $notification->object();
        expect($object)->toMatchArray(['modelClass' => 'Duty', 'name' => 'Test Duty', 'id' => $duty->id]);
    });
});

/*
|--------------------------------------------------------------------------
| MeetingReminderNotification Tests
|--------------------------------------------------------------------------
*/

describe('MeetingReminderNotification', function (): void {
    test('has correct category', function (): void {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->category())->toBe(NotificationCategory::Meeting);
    });

    test('returns soon title when 2 hours or less', function (): void {
        $meeting = Meeting::factory()->create();
        $user = User::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 2);

        $title = $notification->title($user);
        expect($title)->toBeString();
    });

    test('returns regular title when more than 2 hours', function (): void {
        $meeting = Meeting::factory()->create();
        $user = User::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        $title = $notification->title($user);
        expect($title)->toBeString();
    });

    test('uses alarm icon when 2 hours or less', function (): void {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 2);

        expect($notification->icon())->toBe('⏰');
    });

    test('uses calendar icon when more than 2 hours', function (): void {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->icon())->toBe('🗓️');
    });

    test('returns MEETING as modelClass', function (): void {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->modelClass())->toBe('MEETING');
    });

    test('does not support email digest (time-sensitive)', function (): void {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('has action buttons', function (): void {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        $actions = $notification->actions();
        expect($actions)->toBeArray()->not->toBeEmpty();
    });
});

/*
|--------------------------------------------------------------------------
| MemberRegistrationNotification Tests
|--------------------------------------------------------------------------
*/

describe('MemberRegistrationNotification', function (): void {
    test('has correct category', function (): void {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->category())->toBe(NotificationCategory::Registration);
    });

    test('returns correct title', function (): void {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->title($user))->toBeString();
    });

    test('body mentions member name and institution', function (): void {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url to form', function (): void {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->url())->toBe(route('forms.show', 'form-123'));
    });

    test('returns FORM as modelClass', function (): void {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->modelClass())->toBe('FORM');
    });

    test('via includes mail channel', function (): void {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        $channels = $notification->via($user);
        expect($channels)->toContain('mail');
    });

    test('has action buttons', function (): void {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        $actions = $notification->actions();
        expect($actions)->toBeArray()->not->toBeEmpty();
    });
});

/*
|--------------------------------------------------------------------------
| StudentRepRegistrationNotification Tests
|--------------------------------------------------------------------------
*/

describe('StudentRepRegistrationNotification', function (): void {
    test('has correct category', function (): void {
        $institution = Institution::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->category())->toBe(NotificationCategory::Registration);
    });

    test('returns correct title', function (): void {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->title($user))->toBeString();
    });

    test('body mentions rep name and institution', function (): void {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url to form', function (): void {
        $institution = Institution::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->url())->toBe(route('forms.show', 'form-123'));
    });

    test('returns FORM as modelClass', function (): void {
        $institution = Institution::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->modelClass())->toBe('FORM');
    });

    test('via includes mail channel', function (): void {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        $channels = $notification->via($user);
        expect($channels)->toContain('mail');
    });
});

/*
|--------------------------------------------------------------------------
| WelcomeNotification Tests
|--------------------------------------------------------------------------
*/

describe('WelcomeNotification', function (): void {
    test('has correct category', function (): void {
        $notification = new WelcomeNotification;

        expect($notification->category())->toBe(NotificationCategory::System);
    });

    test('returns correct title', function (): void {
        $user = User::factory()->create();
        $notification = new WelcomeNotification;

        expect($notification->title($user))->toBeString();
    });

    test('body mentions user name', function (): void {
        $user = User::factory()->create(['name' => 'Test User']);
        $notification = new WelcomeNotification;

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns dashboard url', function (): void {
        $notification = new WelcomeNotification;

        expect($notification->url())->toBe(route('dashboard'));
    });

    test('uses celebration icon', function (): void {
        $notification = new WelcomeNotification;

        expect($notification->icon())->toBe('🎉');
    });

    test('does not support email digest', function (): void {
        $notification = new WelcomeNotification;

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('has empty actions', function (): void {
        $notification = new WelcomeNotification;

        $actions = $notification->actions();
        expect($actions)->toBeArray()
            ->toBeEmpty();
    });
});

/*
|--------------------------------------------------------------------------
| TestPushNotification Tests
|--------------------------------------------------------------------------
*/

describe('TestPushNotification', function (): void {
    test('has correct category', function (): void {
        $notification = new TestPushNotification;

        expect($notification->category())->toBe(NotificationCategory::System);
    });

    test('returns correct title', function (): void {
        $user = User::factory()->create();
        $notification = new TestPushNotification;

        expect($notification->title($user))->toBeString();
    });

    test('returns correct body', function (): void {
        $user = User::factory()->create();
        $notification = new TestPushNotification;

        expect($notification->body($user))->toBeString();
    });

    test('returns profile url', function (): void {
        $notification = new TestPushNotification;

        expect($notification->url())->toBe(route('profile'));
    });

    test('uses bell icon', function (): void {
        $notification = new TestPushNotification;

        expect($notification->icon())->toBe('🔔');
    });

    test('does not support email digest', function (): void {
        $notification = new TestPushNotification;

        expect($notification->supportsEmailDigest())->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| AssignedToResourceNotification Tests
|--------------------------------------------------------------------------
*/

describe('AssignedToResourceNotification', function (): void {
    test('determines category based on resource type - Reservation', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Reservation', 'name' => 'Test Res', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::Reservation);
    });

    test('determines category based on resource type - Task', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('determines category based on resource type - Meeting', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Meeting', 'name' => 'Test Meeting', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::Meeting);
    });

    test('defaults to User category for unknown resource types', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Unknown', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::User);
    });

    test('returns correct title with resource name', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Important Task', 'url' => '/test'];
        $user = User::factory()->create();
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions assigner and resource', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Assigner Name'];
        $resource = ['modelClass' => 'Task', 'name' => 'Task Name', 'url' => '/test'];
        $user = User::factory()->create();
        $notification = new AssignedToResourceNotification($assigner, $resource);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('uses link icon', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->icon())->toBe('🔗');
    });

    test('returns assigner as subject', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Assigner Name', 'image' => 'photo.jpg'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->subject())->toBe($assigner);
    });

    test('returns resource as object', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/test', 'id' => '123'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->object())->toBe($resource);
    });

    test('maps modelClass correctly for different resource types', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];

        $reservationResource = ['modelClass' => 'Reservation', 'name' => 'Test', 'url' => '/test'];
        $notification1 = new AssignedToResourceNotification($assigner, $reservationResource);
        expect($notification1->modelClass())->toBe('RESERVATION');

        $taskResource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification2 = new AssignedToResourceNotification($assigner, $taskResource);
        expect($notification2->modelClass())->toBe('TASK');

        $meetingResource = ['modelClass' => 'Meeting', 'name' => 'Test', 'url' => '/test'];
        $notification3 = new AssignedToResourceNotification($assigner, $meetingResource);
        expect($notification3->modelClass())->toBe('MEETING');
    });

    test('supports email digest', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->supportsEmailDigest())->toBeTrue();
    });

    test('does not include mail in via channels', function (): void {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $user = User::factory()->create();
        $notification = new AssignedToResourceNotification($assigner, $resource);

        $channels = $notification->via($user);
        expect($channels)->not->toContain('mail');
    });
});
