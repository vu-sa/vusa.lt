<?php

use App\Enums\NotificationType;
use App\Enums\SupportRequestStatus;
use App\Mail\NotificationDigest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\ResponsibilityGrant;
use App\Models\SupportRequest;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BaseNotification;
use App\Notifications\CommentPostedNotification;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Notifications\MeetingCreatedNotification;
use App\Notifications\MemberRegistrationNotification;
use App\Notifications\StudentRepRegistrationNotification;
use App\Notifications\SupportRequestAssignedNotification;
use App\Notifications\SupportRequestStatusChangedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskAutoCompletedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Services\MailPreview\MailPreviewCatalog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Notification;
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
        $notification = new TaskAssignedNotification(Task::factory()->create(['name' => 'Trumpa užduotis']));

        $subject = renderNotificationMail($notification, $this->recipient)['subject'];

        expect(mb_strlen($subject))->toBeLessThanOrEqual(60)
            ->and(preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $subject))->toBe(0)
            ->and($subject)->toBe($notification->title($this->recipient));
    });

    test('a long title stays complete while the email subject is cut to 60 characters', function (): void {
        $task = Task::factory()->create(['name' => str_repeat('Ilgas pavadinimas ', 8)]);
        $notification = new TaskAssignedNotification($task);

        $mail = $notification->toMail($this->recipient);

        expect(mb_strlen($mail->subject))->toBeLessThanOrEqual(60)
            ->and($notification->title($this->recipient))->toBe('Nauja užduotis: '.$task->name)
            ->and($mail->viewData['title'])->toBe($notification->title($this->recipient));
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

test('the chair registration email subject follows the recipient locale', function (): void {
    $institution = (new Institution)->forceFill(['name' => ['lt' => 'Taryba', 'en' => 'Council'], 'short_name' => ['lt' => 'Taryba', 'en' => 'Council']]);
    app()->setLocale('en');

    $mail = new MemberRegistrationNotification(1, 'Jonas', $institution, 'chair@example.org', '1')->toMail($this->recipient);

    expect($mail->subject)->toBe('Member registration: Jonas')
        ->and($mail->viewData['body'])->toContain('Council');
});

describe('digest email', function (): void {
    test('the overdue line lists the tasks without repeating the title or a false "and more"', function (): void {
        $tasks = fn (int $count) => collect(range(1, $count))->map(fn (int $index) => (new Task)->forceFill(['name' => "Užduotis {$index}"]));

        expect(new TaskOverdueNotification($tasks(2))->body($this->recipient))->toBe('Užduotis 1, Užduotis 2')
            ->and(new TaskOverdueNotification($tasks(5))->body($this->recipient))->toBe('Užduotis 1, Užduotis 2, Užduotis 3 ir dar 2');
    });

    test('its plain-text part shows links as text, not markdown', function (): void {
        $text = (string) app(Markdown::class)->renderText('emails.notification-digest', new NotificationDigest($this->recipient, [
            'task' => [['category' => 'task', 'title' => 'Užduotis', 'body' => 'Tekstas', 'url' => 'https://example.org/task']],
        ])->content()->with);

        expect($text)->toContain(rtrim(__('notifications.digest_footer'), '.').': '.route('profile.notifications'))
            ->not->toContain('](');
    });

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

    expect(collect($notification->context($this->recipient))->pluck('value'))->not->toContain($this->recipient->name)
        ->and($notification->toDigestItem($this->recipient)['context'])->toBeEmpty();

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
        expect($content)->toContain('New meetings created (7)', 'Institution 1, 11-01', 'Institution 5, 11-01', 'https://example.test/meeting/5', '+ dar 2 pranešimai', 'Full task body')
            ->not->toContain('+ 2', 'Institution 6', 'Institution 7', 'Full title', 'Full body', 'Unneeded context', 'Unneeded action')
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

test('comment quotes render as escaped text without interpreting markdown', function (string $locale): void {
    app()->setLocale($locale);
    $notification = new CommentPostedNotification(
        '<p>**Svarbu** &amp; &lt;img src=x onerror=alert(1)&gt; [nuoroda](https://untrusted.test)</p><p>Kitas sakinys.</p>',
        ['modelClass' => 'Task', 'name' => 'Planavimas', 'url' => 'https://example.test/task'],
        ['modelClass' => 'User', 'name' => 'Petras Komentatorius'],
    );

    $rendered = renderNotificationMail($notification, $this->recipient);

    expect($rendered['html'])->toContain('**Svarbu**', '&amp;', '&lt;img', 'Kitas sakinys.')
        ->not->toContain('&amp;amp;', '<img src="x"', '<strong>Svarbu</strong>', 'href="https://untrusted.test"', '<pre>')
        ->and($rendered['text'])->toContain(
            ($locale === 'lt' ? '„' : '“').'**Svarbu** & <img src=x onerror=alert(1)>',
            'Kitas sakinys.'.($locale === 'lt' ? '“' : '”'),
        );
})->with(['lt', 'en']);

test('comment excerpts render in single and grouped digests in html and text', function (bool $grouped, string $locale): void {
    app()->setLocale($locale);
    $notification = new CommentPostedNotification(
        '<p>**Svarbu** &amp; aišku.</p>',
        ['modelClass' => 'Task', 'name' => 'Planavimas', 'url' => 'https://example.test/task', 'id' => '1'],
        ['modelClass' => 'User', 'name' => 'Petras'],
    );
    $items = [$notification->toDigestItem($this->recipient)];
    if ($grouped) {
        $items[] = $notification->toDigestItem($this->recipient);
    }
    $digest = new NotificationDigest($this->recipient, ['comment' => $items]);
    $markdown = new Markdown(view(), config('mail.markdown'));

    $html = (string) $markdown->render('emails.notification-digest', $digest->content()->with);
    $text = (string) $markdown->renderText('emails.notification-digest', $digest->content()->with);

    $quote = $locale === 'lt' ? '„**Svarbu** & aišku.“' : '“**Svarbu** & aišku.”';

    expect(html_entity_decode($html))->toContain($quote)
        ->and($html)->not->toContain('&amp;amp;', '<strong>Svarbu</strong>')
        ->and($text)->toContain($quote);

    if (! $grouped) {
        expect($text)->toContain("\n  ".$quote);
    }
})->with([false, true])->with(['lt', 'en']);

test('grouped comments keep each record in its own line under a generic heading', function (string $locale, string $heading, bool $isMention): void {
    app()->setLocale($locale);
    $first = new CommentPostedNotification(
        '<p>First comment.</p>',
        ['modelClass' => 'Task', 'name' => 'Record Alpha', 'url' => 'https://example.test/alpha'],
        ['modelClass' => 'User', 'name' => 'Jonas'],
        isMention: $isMention,
    );
    $second = new CommentPostedNotification(
        '<p>Second comment.</p>',
        ['modelClass' => 'Meeting', 'name' => 'Record Beta', 'url' => 'https://example.test/beta'],
        ['modelClass' => 'User', 'name' => 'Ona'],
        isMention: $isMention,
    );
    $items = [$first->toDigestItem($this->recipient), $second->toDigestItem($this->recipient)];
    $digest = new NotificationDigest($this->recipient, ['comment' => $items]);
    $markdown = new Markdown(view(), config('mail.markdown'));

    foreach ($items as $index => $item) {
        expect($item['digestGroupTitle'])->toBe($heading)
            ->and(substr_count($item['digestLine'], $index === 0 ? 'Record Alpha' : 'Record Beta'))->toBe(1)
            ->and($item['digestLine'])->not->toContain($index === 0 ? 'Record Beta' : 'Record Alpha');
    }
    foreach ([$markdown->render('emails.notification-digest', $digest->content()->with), $markdown->renderText('emails.notification-digest', $digest->content()->with)] as $part) {
        $content = html_entity_decode((string) $part);
        expect($content)->toContain($heading.' (2)', $items[0]['digestLine'], $items[1]['digestLine'])
            ->and(substr_count($content, 'Record Alpha'))->toBe(1)
            ->and(substr_count($content, 'Record Beta'))->toBe(1);
    }
})->with([['lt', 'Nauji komentarai'], ['en', 'New comments']])->with([false, true]);

test('registration notifications render the standard footer and details in HTML and text', function (string $locale, string $kind): void {
    app()->setLocale($locale);
    $institution = Institution::factory()->make(['name' => ['lt' => 'Pavyzdinė taryba', 'en' => 'Sample council']]);
    $notification = $kind === 'member'
        ? new MemberRegistrationNotification(1, 'Jonas Jonaitis', $institution, 'chair@example.org', '1')
        : new StudentRepRegistrationNotification('1', 'Jonas Jonaitis', $institution, '1');

    $rendered = renderNotificationMail($notification, $this->recipient);

    foreach (['html', 'text'] as $part) {
        $content = html_entity_decode($rendered[$part]);
        expect($content)->toContain('Jonas Jonaitis', $institution->name, __('notifications.mail.sign_off'), __('notifications.mail.settings_link'), route('profile.notifications'), $notification->url())
            ->and(substr_count($content, 'Jonas Jonaitis'))->toBe(1);
    }
})->with(['lt', 'en'])->with(['member', 'student-rep']);

test('support request status mail renders in English when delivery requests that locale', function (bool $withUpdater): void {
    app()->setLocale('lt');
    config(['mail.default' => 'array']);
    $request = SupportRequest::factory()->make(['id' => '01J00000000000000000000000', 'title' => 'Broken projector']);
    $notification = new SupportRequestStatusChangedNotification(
        $request, SupportRequestStatus::New, SupportRequestStatus::InProgress,
        $withUpdater ? User::factory()->make(['name' => 'Ona Onaitė']) : null,
    );

    Notification::sendNow($this->recipient, $notification->locale('en'), ['mail']);

    $message = collect(app('mail.manager')->mailer('array')->getSymfonyTransport()->messages())->last()->getOriginalMessage();
    expect($message->getSubject())->toBe('Request status: Broken projector');
    foreach ([$message->getHtmlBody(), $message->getTextBody()] as $part) {
        $content = html_entity_decode($part);
        expect($content)->toContain('Broken projector', 'In progress')
            ->not->toContain('Užklausos', 'pakeitė')
            ->and(substr_count($content, 'Broken projector'))->toBe(1);
    }
})->with([false, true]);

test('grouped request digests retain every request name without using the first as the heading', function (): void {
    app()->setLocale('en');
    $actor = User::factory()->make(['name' => 'Ona']);
    $items = collect(['Broken projector', 'Missing keys'])->map(fn (string $title) => new SupportRequestAssignedNotification(SupportRequest::factory()->make(['id' => '01J00000000000000000000000', 'title' => $title]), $actor)->toDigestItem($this->recipient)
    )->all();
    $digest = new NotificationDigest($this->recipient, ['system' => $items]);
    $markdown = app(Markdown::class);

    foreach ([$markdown->render('emails.notification-digest', $digest->content()->with), $markdown->renderText('emails.notification-digest', $digest->content()->with)] as $part) {
        $content = html_entity_decode((string) $part);
        expect($content)->toContain('Broken projector', 'Missing keys')
            ->and(substr_count($content, 'Broken projector'))->toBe(1)
            ->and(substr_count($content, 'Missing keys'))->toBe(1);
    }
});

test('meeting emails name the meeting in the body, not only in the subject', function (): void {
    app()->setLocale('lt');
    $senate = Institution::factory()->create(['name' => ['lt' => 'Senatas', 'en' => 'Senate']]);
    $council = Institution::factory()->create(['name' => ['lt' => 'Taryba', 'en' => 'Council']]);
    $upcoming = Meeting::factory()->hasAttached($senate)->create(['start_time' => now()->addWeek()]);
    $joint = Meeting::factory()->hasAttached($senate)->create(['start_time' => now()->subDay()]);
    $joint->institutions()->attach($council);

    expect((new MeetingCreatedNotification($upcoming))->body($this->recipient))->toStartWith('Institucijos „Senatas“ posėdis vyks ')
        ->and((new MeetingCreatedNotification($joint->fresh()))->body($this->recipient))->toStartWith('Institucijų „Senatas“ ir „Taryba“ jungtinis posėdis vyko ');
});

test('the agenda summary agrees with the item count in Lithuanian', function (int $count, string $items): void {
    app()->setLocale('lt');
    $meeting = Meeting::factory()->hasAttached(Institution::factory())->create();
    AgendaItem::factory()->count($count)->informational()->create(['meeting_id' => $meeting->id]);

    expect((new MeetingAgendaCompletedNotification($meeting, User::factory()->create(['name' => 'Ona Onaitė'])))->body($this->recipient))
        ->toBe("Ona Onaitė užpildė visą darbotvarkę – {$items} (aptarimas: {$count}).");
})->with([
    'one' => [1, '1 klausimą'],
    'few' => [3, '3 klausimus'],
    'many' => [11, '11 klausimų'],
]);

test('registration confirmations go to people without platform access, so they end with vusa.lt', function (string $preview, string $locale): void {
    $rendered = app(MailPreviewCatalog::class)->render($preview, $locale);

    app()->setLocale($locale);
    expect($rendered['html'].$rendered['text'])
        ->toContain(__('mail.public_description'), __('mail.open_public_site'))
        ->not->toContain(__('mail.platform_description'), route('dashboard'));
})->with(['member-confirmation', 'student-rep-confirmation'])->with(['lt', 'en']);
