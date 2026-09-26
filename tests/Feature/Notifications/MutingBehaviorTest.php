<?php

use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Models\Institution;
use App\Models\Task;
use App\Notifications\CommentPostedNotification;
use App\Notifications\MemberRegistrationNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\Feature\Notifications\NotificationTestHelpers;

pest()->use(RefreshDatabase::class, NotificationTestHelpers::class);

function mutingTestMention(): CommentPostedNotification
{
    return new CommentPostedNotification(
        'Test',
        ['modelClass' => 'Task', 'name' => 'Test', 'url' => '/test', 'id' => '1'],
        ['modelClass' => 'User', 'name' => 'Test'],
        isMention: true,
    );
}

describe('global muting', function (): void {
    test('a muted user still gets the in-app record, but no email or push', function (): void {
        $user = $this->createMutedUser(now()->addHours(2));

        expect(mutingTestMention()->via($user))->toBe(['database', 'broadcast']);
    });

    test('email and push resume once the mute expires', function (): void {
        $user = $this->createMutedUser(now()->addMinutes(30));

        $this->travelTo(now()->addMinutes(31));

        expect(mutingTestMention()->via($user))->toContain('mail', WebPushChannel::class);
    });

    test('role-inbox registration mail is sent even while muted', function (): void {
        $user = $this->createMutedUser(now()->addHours(2));
        $notification = new MemberRegistrationNotification(1, 'Jonas', Institution::factory()->create(), 'duty@vusa.lt', 'form-id');

        expect($notification->via($user))->toBe(['database', 'broadcast', 'mail']);
    });
});

describe('per-type preferences', function (): void {
    test('defaults follow the type: a mention mails and pushes at once, a new task waits for the digest', function (): void {
        $user = $this->createUserWithPreferences();
        $task = Task::factory()->create(['due_date' => now()->addDay()]);

        expect(mutingTestMention()->via($user))->toContain('mail', WebPushChannel::class)
            ->and((new TaskAssignedNotification($task))->via($user))->toBe(['database', 'broadcast'])
            ->and($user->emailDeliveryFor(NotificationType::TaskAssigned))->toBe(EmailDelivery::Digest);
    });

    test('turning email off for one type stops its mail and leaves other types alone', function (): void {
        $user = $this->createUserWithTypePreference(NotificationType::TaskReminder, ['email' => 'off']);
        $task = Task::factory()->create(['due_date' => now()->addDays(3)]);

        expect((new TaskReminderNotification($task, 3))->via($user))->not->toContain('mail')
            ->and(mutingTestMention()->via($user))->toContain('mail');
    });

    test('turning push off for a type drops only the push', function (): void {
        $user = $this->createUserWithTypePreference(NotificationType::CommentMention, ['push' => false]);

        expect(mutingTestMention()->via($user))->toBe(['database', 'broadcast', 'mail']);
    });

    test('a know-tier type can be asked for at once', function (): void {
        $user = $this->createUserWithTypePreference(NotificationType::TaskAssigned, ['email' => 'immediate']);
        $task = Task::factory()->create(['due_date' => now()->addMonth()]);

        expect((new TaskAssignedNotification($task))->via($user))->toContain('mail');
    });

    test('a locked type ignores a stored override', function (): void {
        $user = $this->createUserWithTypePreference(NotificationType::MemberRegistration, ['email' => 'off']);

        expect($user->emailDeliveryFor(NotificationType::MemberRegistration))->toBe(EmailDelivery::Immediate);
    });
});
