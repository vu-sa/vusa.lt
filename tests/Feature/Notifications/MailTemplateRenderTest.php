<?php

use App\Enums\NotificationType;
use App\Mail\NotificationDigest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\Meeting;
use App\Models\ResponsibilityGrant;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BaseNotification;
use App\Notifications\CommentPostedNotification;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Notifications\MeetingCreatedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskAutoCompletedNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Str;

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
    $duty = Duty::factory()->for($institution)->create(['email' => $email]);
    ResponsibilityGrant::factory()->forDuty($duty)->forTenant($institution->tenant)->create();

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
            ->toContain($institution->name, $deadline, $actionUrl, route('profile'), 'Mano VU SA', 'button-neutral')
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

    test('"Ar vyko posėdis?" draws both answers as buttons and offers "not mine" as a link', function (): void {
        $notification = new InstitutionActivityNotification(new EloquentCollection([InstitutionActivityRequest::factory()->create()]));

        $rendered = renderNotificationMail($notification, $this->recipient);

        expect($rendered['html'])->toContain(e($notification->primaryAction()['url']), e($notification->secondaryAction()['url']), 'button-neutral', __('notifications.action_not_mine'))
            ->and($rendered['text'])->toContain($notification->secondaryAction()['url'], $notification->secondaryAction()['label']);
    });

    test('any other second action stays a plain link, so no other mail changes', function (): void {
        $notification = new class extends BaseNotification
        {
            public function type(): NotificationType
            {
                return NotificationType::TaskReminder;
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

    test('an institution email signs off as Mano VU SA, not on behalf of its coordinator', function (): void {
        $tenant = Tenant::query()->where('type', '!=', 'pkp')->first() ?? Tenant::factory()->create(['type' => 'padalinys']);
        $institution = Institution::factory()->for($tenant)->create();
        coordinatorFor($institution, 'koordinatoriai@vusa.lt');

        $request = InstitutionActivityRequest::factory()->for($institution)->create();
        $rendered = renderNotificationMail(new InstitutionActivityNotification(new EloquentCollection([$request])), $this->recipient);

        expect($rendered['text'])->toContain(__('notifications.mail.sign_off'))
            ->not->toContain('Ona Koordinatorė', 'koordinatoriai@vusa.lt');
    });

    test('every email signs off as Mano VU SA, never the system', function (): void {
        $rendered = renderNotificationMail(new TaskAutoCompletedNotification(Task::factory()->create(), 'Patvirtinta'), $this->recipient);

        expect($rendered['text'])->toContain(__('notifications.mail.sign_off'))
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

test('activity mail keeps both choices and the reporting period in HTML and text', function (string $locale, string $campaign): void {
    app()->setLocale($locale);
    $request = InstitutionActivityRequest::factory()->create([
        'campaign_type' => $campaign,
        'period_start' => '2026-09-01',
        'period_end' => '2026-09-30',
        'requested_by_id' => $this->recipient->id,
        'note' => 'Please reply by Friday.',
    ]);
    $notification = new InstitutionActivityNotification(new EloquentCollection([$request]));

    $rendered = renderNotificationMail($notification, $this->recipient);

    foreach (['html', 'text'] as $part) {
        $content = html_entity_decode($rendered[$part]);
        expect($content)->toContain('2026-09-01', '2026-09-30', 'Please reply by Friday.', $notification->primaryAction()['url'], $notification->secondaryAction()['url'])
            ->and(substr_count($content, 'Please reply by Friday.'))->toBe(1)
            ->and(substr_count($content, '2026-09-01'))->toBe(1);
    }
    expect($rendered['html'])->toContain('button-neutral')->not->toContain('table-layout:fixed', '#bd1737')
        ->and($rendered['subject'])->not->toStartWith('VU SA ·');
})->with(['lt', 'en'])->with(['activity_confirmation', 'missing_meetings']);

test('notification emails greet the recipient by first name in their locale', function (string $locale, string $name, string $greeting): void {
    app()->setLocale($locale);
    $this->recipient->name = $name;
    $notification = new TaskAssignedNotification(Task::factory()->create());

    $rendered = renderNotificationMail($notification, $this->recipient);

    foreach (['html', 'text'] as $part) {
        expect($rendered[$part])->toContain($greeting, __('mail.platform_description'), route('dashboard'));
    }
})->with([
    ['lt', 'Jonas Jonaitis', 'Labas, Jonai!'],
    ['lt', 'Agnė Agnaitė', 'Labas, Agne!'],
    ['lt', 'Paulius Paulaitis', 'Labas, Pauliau!'],
    ['lt', 'Jonai Jonaiti', 'Labas, Jonai!'],
    ['lt', '', 'Labas!'],
    ['en', 'Jonas Jonaitis', 'Hello, Jonas!'],
    ['en', 'John Smith', 'Hello, John!'],
]);

test('activity emails keep the platform description and link in the footer', function (): void {
    app()->setLocale('lt');
    $this->recipient->name = 'Agnė Agnaitė';
    $notification = new InstitutionActivityNotification(new EloquentCollection([InstitutionActivityRequest::factory()->create()]));

    $rendered = renderNotificationMail($notification, $this->recipient);
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$rendered['html']);
    $xpath = new DOMXPath($document);
    $footer = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " footer ")]')->item(0);

    expect($rendered['html'])->toContain('Labas, Agne!', 'kaip studentų atstovo (-ės)', 'įjungta')
        ->not->toContain('įjungei', 'Posėdžius ir darbotvarkes rasi')
        ->and($footer->textContent)->toContain(__('mail.platform_description'), __('mail.open_platform'))
        ->and($xpath->query('.//a', $footer)->item(0)->getAttribute('href'))->toBe(route('dashboard'))
        ->and($rendered['text'])->toContain('Labas, Agne!', __('mail.platform_description'), route('dashboard'));
});

test('digest greetings also use the Lithuanian first-name ending', function (): void {
    app()->setLocale('lt');
    $this->recipient->name = 'Jonas Jonaitis';
    $digest = new NotificationDigest($this->recipient, ['task' => [['title' => 'Užduotis', 'body' => 'Turinys', 'url' => '/x']]]);
    $markdown = new Markdown(view(), config('mail.markdown'));

    foreach ([$digest->render(), $markdown->renderText('emails.notification-digest', $digest->content()->with)] as $content) {
        expect((string) $content)->toContain('Labas, Jonai!', __('mail.platform_description'));
    }
});

test('digest subjects use the single title or count and ordered categories', function (string $locale): void {
    app()->setLocale($locale);
    $item = ['title' => 'Užpildyk darbotvarkę', 'body' => 'Turinys', 'url' => '/x'];
    $single = new NotificationDigest($this->recipient, ['meeting' => [$item]]);
    $multiple = new NotificationDigest($this->recipient, ['meeting' => [$item], 'task' => [$item, $item, $item, $item]]);
    $long = new NotificationDigest($this->recipient, ['task' => [array_replace($item, ['title' => str_repeat('Ilgas pavadinimas ', 10)])]]);

    expect($single->envelope()->subject)->toBe($item['title'])
        ->and($multiple->envelope()->subject)->toBe(trans_choice('notifications.digest_subject_categories', 5, [
            'count' => 5,
            'categories' => Str::lower(__('notifications.categories.task').', '.__('notifications.categories.meeting')),
        ]))
        ->and(mb_strlen($long->envelope()->subject))->toBeLessThanOrEqual(60)
        ->and($long->envelope()->subject)->toEndWith('…');
})->with(['lt', 'en']);

test('digest groups repeated types and caps each category at five linked lines', function (): void {
    $items = [];
    for ($i = 1; $i <= 7; $i++) {
        $items[] = [
            'type' => 'meeting_created', 'digestGroupTitle' => 'New meetings created',
            'digestLine' => "Institution $i, 11-01", 'title' => "Full title $i", 'body' => "Full body $i",
            'url' => "https://example.test/meeting/$i",
            'context' => [['label' => 'Unneeded context', 'value' => 'Value']],
            'primaryAction' => ['label' => 'Unneeded action', 'url' => '/action'],
        ];
    }
    $single = ['title' => 'Single task', 'body' => 'Full task body', 'url' => '/task', 'type' => 'task_assigned'];
    $digest = new NotificationDigest($this->recipient, ['meeting' => $items, 'task' => [$single]]);
    $markdown = new Markdown(view(), config('mail.markdown'));
    $with = $digest->content()->with;

    foreach ([$markdown->render('emails.notification-digest', $with), $markdown->renderText('emails.notification-digest', $with)] as $part) {
        $content = html_entity_decode((string) $part);
        expect($content)->toContain('New meetings created (7)', 'Institution 1, 11-01', 'Institution 5, 11-01', 'https://example.test/meeting/5', '+ 2', 'Full task body')
            ->not->toContain('Institution 6', 'Institution 7', 'Full title', 'Full body', 'Unneeded context', 'Unneeded action')
            ->and(strpos($content, 'Single task'))->toBeLessThan(strpos($content, 'New meetings created'));
    }
});

test('single types and legacy items retain full rows in both digest parts', function (): void {
    $legacy = ['title' => 'Legacy title', 'body' => 'Legacy body', 'url' => '/legacy'];
    $single = [
        'type' => 'meeting_created', 'digestGroupTitle' => 'Group heading', 'digestLine' => 'Compact line',
        'title' => 'Full meeting title', 'body' => 'Full meeting body', 'url' => '/meeting',
        'context' => [['label' => 'Date', 'value' => '2026-11-01']],
        'primaryAction' => ['label' => 'Open meeting', 'url' => '/meeting-action'],
    ];
    $digest = new NotificationDigest($this->recipient, ['meeting' => [$legacy, $legacy, $single]]);
    $markdown = new Markdown(view(), config('mail.markdown'));

    foreach ([$markdown->render('emails.notification-digest', $digest->content()->with), $markdown->renderText('emails.notification-digest', $digest->content()->with)] as $part) {
        $content = html_entity_decode((string) $part);
        expect(substr_count($content, 'Legacy body'))->toBe(2)
            ->and($content)->toContain('Full meeting title', 'Full meeting body', 'Date: 2026-11-01', '/meeting-action')
            ->not->toContain('Group heading', 'Compact line');
    }
});

test('activity questions never collapse even when they share a type', function (): void {
    $request = InstitutionActivityRequest::factory()->create();
    $item = new InstitutionActivityNotification(new EloquentCollection([$request]))->toDigestItem($this->recipient);
    $digest = new NotificationDigest($this->recipient, ['meeting' => [$item, $item]]);
    $markdown = new Markdown(view(), config('mail.markdown'));

    foreach ([$markdown->render('emails.notification-digest', $digest->content()->with), $markdown->renderText('emails.notification-digest', $digest->content()->with)] as $part) {
        expect(substr_count(html_entity_decode((string) $part), $item['activity_questions'][0]['notMine']))->toBe(2);
    }
});

test('digest category order is fixed with unknown categories stable at the end', function (): void {
    $item = ['title' => 'Title', 'body' => 'Body', 'url' => '/x'];
    $digest = new NotificationDigest($this->recipient, array_fill_keys([
        'unknown-b', 'system', 'user', 'registration', 'duty', 'comment', 'reservation', 'meeting', 'unknown-a', 'task',
    ], [$item]));

    expect(array_keys($digest->content()->with['categorizedItems']))->toBe([
        __('notifications.categories.task'), __('notifications.categories.meeting'), __('notifications.categories.reservation'),
        __('notifications.categories.comment'), __('notifications.categories.duty'), __('notifications.categories.registration'),
        __('notifications.categories.user'), __('notifications.categories.system'), 'unknown-b', 'unknown-a',
    ]);
});

test('meeting digest lines retain the notification type for followers', function (): void {
    $institution = Institution::factory()->create();
    $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-11-01 12:00:00']);
    $created = new MeetingCreatedNotification($meeting);
    $completed = new MeetingAgendaCompletedNotification($meeting);

    expect($created->toDigestItem($this->recipient))->toMatchArray([
        'type' => 'meeting_created', 'digestLine' => $institution->name.', 11-01',
        'digestGroupTitle' => __('notifications.meeting_created_digest_group'),
    ])->and($completed->toDigestItem($this->recipient))->toMatchArray([
        'type' => 'meeting_agenda_completed', 'digestLine' => $institution->name,
        'digestGroupTitle' => __('notifications.meeting_agenda_completed_digest_group'),
    ]);

    $created->viaFollow();
    $completed->viaFollow();
    expect($created->toDigestItem($this->recipient)['type'])->toBe('followed_institution_activity')
        ->and($completed->toDigestItem($this->recipient)['type'])->toBe('followed_institution_activity');
});
