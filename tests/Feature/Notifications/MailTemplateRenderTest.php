<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;
use App\Mail\NotificationDigest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BaseNotification;
use App\Notifications\CommentPostedNotification;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCompletedNotification;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;

pest()->use(RefreshDatabase::class);

/**
 * @return array{subject: string, html: string, text: string}
 */
function renderNotificationMail(BaseNotification $notification, User $user): array
{
    $mail = $notification->toMail($user);
    $markdown = new Markdown(view(), config('mail.markdown'));

    return [
        'subject' => $mail->subject,
        'html' => (string) $markdown->render($mail->markdown, $mail->data()),
        'text' => (string) $markdown->renderText($mail->markdown, $mail->data()),
    ];
}

/**
 * Makes $institution's tenant have a coordinator holding a vusa.lt duty address.
 */
function coordinatorFor(Institution $institution, string $email): User
{
    $role = Role::factory()->create(['guard_name' => 'web']);
    $settings = app(AtstovavimasSettings::class);
    $settings->setInstitutionManagerRoleId($role->id);
    $settings->save();

    $duty = Duty::factory()->for($institution)->create(['email' => $email]);
    $duty->roles()->attach($role);

    $coordinator = User::factory()->create(['name' => 'Ona Koordinatorė']);
    $coordinator->duties()->attach($duty, ['start_date' => now()->subMonth(), 'end_date' => null]);

    return $coordinator;
}

beforeEach(function (): void {
    $this->recipient = User::factory()->create();
});

describe('notification email', function (): void {
    test('renders title, context rows, button and the why-you-got-this footer in html and text', function (): void {
        $institution = Institution::factory()->create();
        $task = Task::factory()->create([
            'taskable_type' => $institution->getMorphClass(),
            'taskable_id' => $institution->id,
            'due_date' => now()->addDays(2),
        ]);
        $notification = new TaskAssignedNotification($task);

        $rendered = renderNotificationMail($notification, $this->recipient);

        $deadline = $task->due_date->format('Y-m-d');
        $actionUrl = $notification->primaryAction()['url'];

        expect($rendered['html'])
            ->toContain($institution->name, $deadline, $actionUrl, route('profile'), 'Mano VU SA')
            ->and($rendered['text'])
            ->toContain($institution->name, $deadline, $actionUrl, route('profile'));
    });

    test('the subject is the title: short and without an emoji prefix', function (): void {
        $notification = new TaskAssignedNotification(Task::factory()->create());

        $subject = renderNotificationMail($notification, $this->recipient)['subject'];

        expect(mb_strlen($subject))->toBeLessThanOrEqual(60)
            ->and(preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $subject))->toBe(0)
            ->and($subject)->toBe($notification->title($this->recipient));
    });

    test('a long title is cut to 60 characters', function (): void {
        $notification = new CommentPostedNotification(
            'Sveiki',
            ['modelClass' => 'Task', 'name' => str_repeat('Ilgas pavadinimas ', 8), 'url' => '/t/1', 'id' => '1'],
            ['modelClass' => 'User', 'name' => 'Jonas'],
        );

        expect(mb_strlen(renderNotificationMail($notification, $this->recipient)['subject']))->toBeLessThanOrEqual(60);
    });

    test('a notification whose second action is an answer draws it as a second button', function (): void {
        $task = Task::factory()->create(['metadata' => ['activity_status' => 'overdue']]);
        $notification = new InstitutionActivityNotification($task, Institution::factory()->create());

        $rendered = renderNotificationMail($notification, $this->recipient);

        expect($rendered['html'])->toContain(e($notification->secondaryAction()['url']), 'button-secondary')
            ->and($rendered['text'])->toContain($notification->secondaryAction()['url'], $notification->secondaryAction()['label']);
    });

    test('any other second action stays a plain link, so no other mail changes', function (): void {
        $notification = new class extends BaseNotification
        {
            public function category(): NotificationCategory
            {
                return NotificationCategory::System;
            }

            public function urgency(): NotificationUrgency
            {
                return NotificationUrgency::Act;
            }

            public function title(object $notifiable): string
            {
                return 'Pavadinimas';
            }

            public function body(object $notifiable): string
            {
                return 'Turinys';
            }

            public function url(): string
            {
                return 'https://example.test/one';
            }

            public function secondaryAction(): ?array
            {
                return ['label' => 'Antras', 'url' => 'https://example.test/two'];
            }
        };

        $rendered = renderNotificationMail($notification, $this->recipient);

        expect($rendered['html'])->toContain('https://example.test/two')->not->toContain('button-secondary');
    });

    test('an institution email is signed by the coordinator, on the duty address', function (): void {
        $tenant = Tenant::query()->where('type', '!=', 'pkp')->first() ?? Tenant::factory()->create(['type' => 'padalinys']);
        $institution = Institution::factory()->for($tenant)->create();
        coordinatorFor($institution, 'koordinatoriai@vusa.lt');

        $rendered = renderNotificationMail(new InstitutionActivityNotification(Task::factory()->create(), $institution), $this->recipient);

        expect($rendered['html'])->toContain('Ona Koordinatorė', 'koordinatoriai@vusa.lt')
            ->and($rendered['text'])->toContain('Ona Koordinatorė', 'koordinatoriai@vusa.lt');
    });

    test('the recipient is never asked to write to themselves', function (): void {
        $tenant = Tenant::query()->where('type', '!=', 'pkp')->first() ?? Tenant::factory()->create(['type' => 'padalinys']);
        $institution = Institution::factory()->for($tenant)->create();
        $coordinator = coordinatorFor($institution, 'koordinatoriai@vusa.lt');

        $rendered = renderNotificationMail(new InstitutionActivityNotification(Task::factory()->create(), $institution), $coordinator);

        expect($rendered['html'])->not->toContain('koordinatoriai@vusa.lt')
            ->and($rendered['text'])->not->toContain(__('notifications.mail.signature_intro'));
    });

    test('without a person to sign it falls back to Mano VU SA, never the system', function (): void {
        $rendered = renderNotificationMail(new TaskCompletedNotification(Task::factory()->create(), User::factory()->create()), $this->recipient);

        expect($rendered['text'])->toContain(__('notifications.mail.sign_off'))
            ->not->toContain(__('notifications.mail.signature_intro'))
            ->not->toContain('SISTEMA');
    });
});

describe('digest email', function (): void {
    test('groups items by category with context rows and a link, in html and text', function (): void {
        $digest = new NotificationDigest($this->recipient, [
            'task' => [[
                'title' => 'Vėluojanti užduotis',
                'body' => 'Užpildyk darbotvarkę',
                'url' => 'https://example.test/task',
                'icon' => '☑️',
                'context' => [['label' => 'Terminas', 'value' => '2026-09-30']],
                'primaryAction' => ['label' => 'Peržiūrėti užduotis', 'url' => 'https://example.test/tasks'],
            ]],
        ]);
        $markdown = new Markdown(view(), config('mail.markdown'));
        $with = $digest->content()->with;

        $html = (string) $markdown->render('emails.notification-digest', $with);
        $text = (string) $markdown->renderText('emails.notification-digest', $with);

        expect($html)->toContain('Vėluojanti užduotis', 'Terminas: 2026-09-30', 'https://example.test/tasks', '#9c522e', route('profile'))
            ->and($text)->toContain('Vėluojanti užduotis', 'Terminas: 2026-09-30', 'https://example.test/tasks')
            ->not->toContain('<table');
    });

    test('an item queued before context existed still renders', function (): void {
        $digest = new NotificationDigest($this->recipient, [
            'comment' => [['title' => 'Senas', 'body' => 'Įrašas', 'url' => '/x', 'icon' => '💬']],
        ]);

        $html = (string) new Markdown(view(), config('mail.markdown'))->render('emails.notification-digest', $digest->content()->with);

        expect($html)->toContain('Senas');
    });
});
