<?php

use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Models\NotificationDigestQueue;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\TaskAssignedNotification;
use App\Services\MailPreview\MailPreviewCatalog;
use App\Services\MailPreview\MailPreviewRenderer;
use App\Services\MailPreview\MailPreviewSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

function previewQueueItem(User $user, string $class = TaskAssignedNotification::class): NotificationDigestQueue
{
    return NotificationDigestQueue::create([
        'user_id' => $user->id, 'notification_class' => $class, 'category' => 'task',
        'data' => ['title' => 'Pavyzdinė užduotis', 'body' => 'Parenk ataskaitą.', 'url' => 'https://example.org/task',
            'activity_request_ids' => [MailPreviewSamples::ID]],
    ]);
}

function previewRoutes(User $user): array
{
    return [
        route('mailPreviews.index'),
        route('mailPreviews.render', 'digest-single'),
        route('mailQueue.previewForUser', $user),
    ];
}

test('all three routes deny a user without the system permission', function (): void {
    $user = makeUser($this->tenant);
    foreach (previewRoutes($user) as $url) {
        asUser($user)->get($url)->assertForbidden();
    }
});

test('a tenant administrator without viewAny Role cannot preview mail', function (): void {
    $user = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);
    expect($user->can('viewAny', Role::class))->toBeFalse();

    foreach (previewRoutes($user) as $url) {
        asUser($user)->get($url)->assertForbidden();
    }
});

test('the system page exposes catalog entries and localized subjects', function (): void {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)->get(route('mailPreviews.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/MailPreviews')
            ->has('previews', count(app(MailPreviewCatalog::class)->entries()))
            ->has('previews.0.subjects.lt')->has('previews.0.subjects.en'));
});

test('a non-super-admin with the same system read permission can use all preview routes', function (): void {
    $reader = makeUser($this->tenant);
    $reader->givePermissionTo('roles.read.*');
    previewQueueItem($reader);

    foreach (previewRoutes($reader) as $url) {
        asUser($reader)->get($url)->assertOk();
    }
});

test('render responses are sandboxed and default to Lithuanian', function (): void {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)->get(route('mailPreviews.render', 'welcome'))->assertOk()
        ->assertHeader('Content-Security-Policy', MailPreviewRenderer::CSP)
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertSee('Vardenis')->assertDontSee('Šio laiško peržiūrėti nepavyko');
});

test('unknown catalog keys return 404', function (): void {
    asUser(makeAdminUser($this->tenant))->get(route('mailPreviews.render', 'unknown'))->assertNotFound();
});

test('render inputs reject unsupported formats and locales', function (): void {
    $admin = makeAdminUser($this->tenant);
    foreach ([['locale' => 'de'], ['locale' => ['lt']], ['format' => 'pdf']] as $query) {
        asUser($admin)->getJson(route('mailPreviews.render', ['preview' => 'welcome', ...$query]))->assertUnprocessable();
    }
});

test('every catalog key renders HTML and real plain text in both locales without sending or querying sample records', function (): void {
    Mail::fake();
    Notification::fake();
    $catalog = app(MailPreviewCatalog::class);
    $queries = [];
    DB::listen(function ($query) use (&$queries): void { $queries[] = $query->sql; });

    foreach (array_keys($catalog->entries()) as $key) {
        foreach (['lt', 'en'] as $locale) {
            $rendered = $catalog->render($key, $locale);
            expect($rendered['html'], $key.' '.$locale)->not->toBeEmpty()
                ->not->toContain('Šio laiško peržiūrėti nepavyko', 'This email could not be previewed');
            expect($rendered['text'], $key.' text '.$locale)->not->toBeEmpty()->not->toContain('<!DOCTYPE', '<html');
            expect($rendered['subject'])->not->toBeEmpty();
        }
    }

    expect($queries)->toBeEmpty();
    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    Notification::assertNothingSent();
});

test('the catalog covers notification types with email and documents the push-only exclusion', function (): void {
    $exclusions = [NotificationType::TestPush]; // Push channel only, even when email preferences change.
    $keys = array_keys(app(MailPreviewCatalog::class)->entries());
    foreach (NotificationType::cases() as $type) {
        if (in_array($type, $exclusions, true)) {
            expect($keys)->not->toContain($type->value);

            continue;
        }
        // Off is a default, not a ban: configurable types can still be mailed.
        if ($type->isConfigurable() || $type->defaultEmail() !== EmailDelivery::Off || $type === NotificationType::Welcome) {
            expect($keys)->toContain($type->value);
        }
    }
    $represented = collect(app(MailPreviewSamples::class)->notifications())->map(fn ($notification) => $notification->type()->value)->all();
    expect($represented)->toEqualCanonicalizing(array_map(fn ($type) => $type->value, MailPreviewCatalog::NOTIFICATION_TYPES));
});

test('real queue previews retain stale activity rows and all other recipients without dispatching anything', function (): void {
    $admin = makeAdminUser($this->tenant);
    $recipient = makeUser($this->tenant);
    previewQueueItem($recipient);
    previewQueueItem($recipient, InstitutionActivityNotification::class);
    previewQueueItem(makeUser($this->tenant));
    $before = NotificationDigestQueue::orderBy('id')->get()->toArray();
    Mail::fake();
    Notification::fake();

    asUser($admin)->get(route('mailQueue.previewForUser', $recipient))->assertOk()
        ->assertHeader('Content-Security-Policy', MailPreviewRenderer::CSP)
        ->assertSee('Pavyzdinė užduotis')->assertSee('Parenk ataskaitą.');
    asUser($admin)->get(route('mailPreviews.render', ['preview' => 'digest-grouped', 'format' => 'text', 'locale' => 'en']))
        ->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    asUser($admin)->get(route('mailPreviews.index'))->assertOk();

    expect(NotificationDigestQueue::orderBy('id')->get()->toArray())->toBe($before);
    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    Notification::assertNothingSent();
});

test('an empty real digest returns 404', function (): void {
    asUser(makeAdminUser($this->tenant))->get(route('mailQueue.previewForUser', makeUser($this->tenant)))->assertNotFound();
});

test('a rendering failure is reported and returns the small fallback page', function (): void {
    Exceptions::fake();
    $exception = new RuntimeException('Broken preview');
    $samples = Mockery::mock(MailPreviewSamples::class);
    $samples->shouldReceive('notifications')->once()->andThrow($exception);
    $catalog = new MailPreviewCatalog($samples, app(MailPreviewRenderer::class));

    $rendered = $catalog->render('welcome');

    expect($rendered['html'])->toContain('Šio laiško peržiūrėti nepavyko')->not->toContain('Broken preview');
    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
});
