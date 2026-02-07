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

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| TaskAssignedNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskAssignedNotification', function () {
    test('has correct category', function () {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns correct title', function () {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->title($user))->toBeString();
    });

    test('returns body with assigner when provided', function () {
        $task = Task::factory()->create();
        $assigner = User::factory()->create(['name' => 'Test Assigner']);
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task, $assigner);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns body without assigner when not provided', function () {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url', function () {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->url())->toBe(route('userTasks'));
    });

    test('returns TASK as modelClass', function () {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->modelClass())->toBe('TASK');
    });

    test('returns subject when assigner is provided', function () {
        $task = Task::factory()->create();
        $assigner = User::factory()->create(['name' => 'Test Assigner']);
        $notification = new TaskAssignedNotification($task, $assigner);

        $subject = $notification->subject();
        expect($subject)->toBeArray();
        expect($subject['modelClass'])->toBe('User');
        expect($subject['name'])->toBe('Test Assigner');
    });

    test('returns null subject when assigner is not provided', function () {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->subject())->toBeNull();
    });

    test('returns correct object structure', function () {
        $task = Task::factory()->create(['name' => 'Test Task']);
        $notification = new TaskAssignedNotification($task);

        $object = $notification->object();
        expect($object)->toBeArray();
        expect($object['modelClass'])->toBe('Task');
        expect($object['name'])->toBe('Test Task');
        expect($object['id'])->toBe($task->id);
    });

    test('has action buttons', function () {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        $actions = $notification->actions();
        expect($actions)->toBeArray();
        expect($actions)->not->toBeEmpty();
    });

    test('supports email digest by default', function () {
        $task = Task::factory()->create();
        $notification = new TaskAssignedNotification($task);

        expect($notification->supportsEmailDigest())->toBeTrue();
    });

    test('uses default via channels without mail', function () {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskAssignedNotification($task);

        $channels = $notification->via($user);
        expect($channels)->toContain('database');
        expect($channels)->toContain('broadcast');
        expect($channels)->toContain(WebPushChannel::class);
        expect($channels)->not->toContain('mail');
    });
});

/*
|--------------------------------------------------------------------------
| TaskCompletedNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskCompletedNotification', function () {
    test('has correct category', function () {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns correct title', function () {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions task name and completer', function () {
        $task = Task::factory()->create(['name' => 'Important Task']);
        $completedBy = User::factory()->create(['name' => 'Completer User']);
        $user = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url', function () {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->url())->toBe(route('userTasks'));
    });

    test('uses checkmark icon', function () {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create();
        $notification = new TaskCompletedNotification($task, $completedBy);

        expect($notification->icon())->toBe('✅');
    });

    test('returns subject with completer info', function () {
        $task = Task::factory()->create();
        $completedBy = User::factory()->create(['name' => 'Completer']);
        $notification = new TaskCompletedNotification($task, $completedBy);

        $subject = $notification->subject();
        expect($subject['name'])->toBe('Completer');
        expect($subject['modelClass'])->toBe('User');
    });

    test('supports email digest', function () {
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

describe('TaskReminderNotification', function () {
    test('has correct category', function () {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns correct title with days left', function () {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions task name and days left', function () {
        $task = Task::factory()->create(['name' => 'Important Task']);
        $user = User::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('uses warning icon when 1 day or less', function () {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 1);

        expect($notification->icon())->toBe('⚠️');
    });

    test('uses alarm icon when more than 1 day', function () {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->icon())->toBe('⏰');
    });

    test('does not support email digest (time-sensitive)', function () {
        $task = Task::factory()->create();
        $notification = new TaskReminderNotification($task, 3);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('returns correct object structure', function () {
        $task = Task::factory()->create(['name' => 'Test Task']);
        $notification = new TaskReminderNotification($task, 3);

        $object = $notification->object();
        expect($object['modelClass'])->toBe('Task');
        expect($object['name'])->toBe('Test Task');
    });
});

/*
|--------------------------------------------------------------------------
| TaskOverdueNotification Tests
|--------------------------------------------------------------------------
*/

describe('TaskOverdueNotification', function () {
    test('has correct category', function () {
        $tasks = collect([Task::factory()->create()]);
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('returns title with task count', function () {
        $tasks = collect([
            Task::factory()->create(),
            Task::factory()->create(),
        ]);
        $user = User::factory()->create();
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->title($user))->toBeString();
    });

    test('body for single task mentions task name', function () {
        $task = Task::factory()->create(['name' => 'Single Overdue Task']);
        $tasks = collect([$task]);
        $user = User::factory()->create();
        $notification = new TaskOverdueNotification($tasks);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('body for multiple tasks mentions count', function () {
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

    test('uses warning icon', function () {
        $tasks = collect([Task::factory()->create()]);
        $notification = new TaskOverdueNotification($tasks);

        expect($notification->icon())->toBe('⚠️');
    });

    test('does not support email digest (important)', function () {
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

describe('DutyExpiringNotification', function () {
    test('has correct category', function () {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->category())->toBe(NotificationCategory::Duty);
    });

    test('returns correct title with days', function () {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $user = User::factory()->create();
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions duty name and date', function () {
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

    test('returns correct url to duty', function () {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->url())->toBe(route('duties.show', $duty->id));
    });

    test('uses bell icon', function () {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->icon())->toBe('🔔');
    });

    test('returns DUTY as modelClass', function () {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->modelClass())->toBe('DUTY');
    });

    test('does not support email digest', function () {
        $duty = Duty::factory()->create();
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('returns correct object structure', function () {
        $duty = Duty::factory()->create(['name' => 'Test Duty']);
        $dutiable = Dutiable::factory()->create([
            'duty_id' => $duty->id,
            'end_date' => now()->addDays(30),
        ]);
        $notification = new DutyExpiringNotification($duty, $dutiable, 30);

        $object = $notification->object();
        expect($object['modelClass'])->toBe('Duty');
        expect($object['name'])->toBe('Test Duty');
        expect($object['id'])->toBe($duty->id);
    });
});

/*
|--------------------------------------------------------------------------
| MeetingReminderNotification Tests
|--------------------------------------------------------------------------
*/

describe('MeetingReminderNotification', function () {
    test('has correct category', function () {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->category())->toBe(NotificationCategory::Meeting);
    });

    test('returns soon title when 2 hours or less', function () {
        $meeting = Meeting::factory()->create();
        $user = User::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 2);

        $title = $notification->title($user);
        expect($title)->toBeString();
    });

    test('returns regular title when more than 2 hours', function () {
        $meeting = Meeting::factory()->create();
        $user = User::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        $title = $notification->title($user);
        expect($title)->toBeString();
    });

    test('uses alarm icon when 2 hours or less', function () {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 2);

        expect($notification->icon())->toBe('⏰');
    });

    test('uses calendar icon when more than 2 hours', function () {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->icon())->toBe('🗓️');
    });

    test('returns MEETING as modelClass', function () {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->modelClass())->toBe('MEETING');
    });

    test('does not support email digest (time-sensitive)', function () {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('has action buttons', function () {
        $meeting = Meeting::factory()->create();
        $notification = new MeetingReminderNotification($meeting, 24);

        $actions = $notification->actions();
        expect($actions)->toBeArray();
        expect($actions)->not->toBeEmpty();
    });
});

/*
|--------------------------------------------------------------------------
| MemberRegistrationNotification Tests
|--------------------------------------------------------------------------
*/

describe('MemberRegistrationNotification', function () {
    test('has correct category', function () {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->category())->toBe(NotificationCategory::Registration);
    });

    test('returns correct title', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->title($user))->toBeString();
    });

    test('body mentions member name and institution', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url to form', function () {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->url())->toBe(route('forms.show', 'form-123'));
    });

    test('returns FORM as modelClass', function () {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        expect($notification->modelClass())->toBe('FORM');
    });

    test('via includes mail channel', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        $channels = $notification->via($user);
        expect($channels)->toContain('mail');
    });

    test('has action buttons', function () {
        $institution = Institution::factory()->create();
        $notification = new MemberRegistrationNotification(
            1, 'Test Member', $institution, 'test@example.com', 'form-123'
        );

        $actions = $notification->actions();
        expect($actions)->toBeArray();
        expect($actions)->not->toBeEmpty();
    });
});

/*
|--------------------------------------------------------------------------
| StudentRepRegistrationNotification Tests
|--------------------------------------------------------------------------
*/

describe('StudentRepRegistrationNotification', function () {
    test('has correct category', function () {
        $institution = Institution::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->category())->toBe(NotificationCategory::Registration);
    });

    test('returns correct title', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->title($user))->toBeString();
    });

    test('body mentions rep name and institution', function () {
        $institution = Institution::factory()->create();
        $user = User::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns correct url to form', function () {
        $institution = Institution::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->url())->toBe(route('forms.show', 'form-123'));
    });

    test('returns FORM as modelClass', function () {
        $institution = Institution::factory()->create();
        $notification = new StudentRepRegistrationNotification(
            'reg-123', 'Test Rep', $institution, 'form-123'
        );

        expect($notification->modelClass())->toBe('FORM');
    });

    test('via includes mail channel', function () {
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

describe('WelcomeNotification', function () {
    test('has correct category', function () {
        $notification = new WelcomeNotification;

        expect($notification->category())->toBe(NotificationCategory::System);
    });

    test('returns correct title', function () {
        $user = User::factory()->create();
        $notification = new WelcomeNotification;

        expect($notification->title($user))->toBeString();
    });

    test('body mentions user name', function () {
        $user = User::factory()->create(['name' => 'Test User']);
        $notification = new WelcomeNotification;

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('returns dashboard url', function () {
        $notification = new WelcomeNotification;

        expect($notification->url())->toBe(route('dashboard'));
    });

    test('uses celebration icon', function () {
        $notification = new WelcomeNotification;

        expect($notification->icon())->toBe('🎉');
    });

    test('does not support email digest', function () {
        $notification = new WelcomeNotification;

        expect($notification->supportsEmailDigest())->toBeFalse();
    });

    test('has empty actions', function () {
        $notification = new WelcomeNotification;

        $actions = $notification->actions();
        expect($actions)->toBeArray();
        expect($actions)->toBeEmpty();
    });
});

/*
|--------------------------------------------------------------------------
| TestPushNotification Tests
|--------------------------------------------------------------------------
*/

describe('TestPushNotification', function () {
    test('has correct category', function () {
        $notification = new TestPushNotification;

        expect($notification->category())->toBe(NotificationCategory::System);
    });

    test('returns correct title', function () {
        $user = User::factory()->create();
        $notification = new TestPushNotification;

        expect($notification->title($user))->toBeString();
    });

    test('returns correct body', function () {
        $user = User::factory()->create();
        $notification = new TestPushNotification;

        expect($notification->body($user))->toBeString();
    });

    test('returns profile url', function () {
        $notification = new TestPushNotification;

        expect($notification->url())->toBe(route('profile'));
    });

    test('uses bell icon', function () {
        $notification = new TestPushNotification;

        expect($notification->icon())->toBe('🔔');
    });

    test('does not support email digest', function () {
        $notification = new TestPushNotification;

        expect($notification->supportsEmailDigest())->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| AssignedToResourceNotification Tests
|--------------------------------------------------------------------------
*/

describe('AssignedToResourceNotification', function () {
    test('determines category based on resource type - Reservation', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Reservation', 'name' => 'Test Res', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::Reservation);
    });

    test('determines category based on resource type - Task', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::Task);
    });

    test('determines category based on resource type - Meeting', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Meeting', 'name' => 'Test Meeting', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::Meeting);
    });

    test('defaults to User category for unknown resource types', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Unknown', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->category())->toBe(NotificationCategory::User);
    });

    test('returns correct title with resource name', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Important Task', 'url' => '/test'];
        $user = User::factory()->create();
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->title($user))->toBeString();
    });

    test('body mentions assigner and resource', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Assigner Name'];
        $resource = ['modelClass' => 'Task', 'name' => 'Task Name', 'url' => '/test'];
        $user = User::factory()->create();
        $notification = new AssignedToResourceNotification($assigner, $resource);

        $body = $notification->body($user);
        expect($body)->toBeString();
    });

    test('uses link icon', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->icon())->toBe('🔗');
    });

    test('returns assigner as subject', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Assigner Name', 'image' => 'photo.jpg'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->subject())->toBe($assigner);
    });

    test('returns resource as object', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test Task', 'url' => '/test', 'id' => '123'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->object())->toBe($resource);
    });

    test('maps modelClass correctly for different resource types', function () {
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

    test('supports email digest', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $notification = new AssignedToResourceNotification($assigner, $resource);

        expect($notification->supportsEmailDigest())->toBeTrue();
    });

    test('does not include mail in via channels', function () {
        $assigner = ['modelClass' => 'User', 'name' => 'Test'];
        $resource = ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test'];
        $user = User::factory()->create();
        $notification = new AssignedToResourceNotification($assigner, $resource);

        $channels = $notification->via($user);
        expect($channels)->not->toContain('mail');
    });
});
