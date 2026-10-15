<?php

use App\Models\Calendar;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\News;
use App\Models\Tenant;
use App\Notifications\InstitutionActivityNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

it('opens an activity answer and home news and events outside the admin Inertia app', function (): void {
    $tenant = Tenant::main();
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $user->update(['tutorial_progress' => array_fill_keys(productTourIds(), now()->toISOString())]);
    $request = InstitutionActivityRequest::factory()->create([
        'institution_id' => Institution::factory()->for($tenant)->create()->id,
        'recipient_id' => $user->id,
    ]);
    $news = News::factory()->create([
        'tenant_id' => $tenant->id,
        'title' => 'Nuorodos naujiena',
        'lang' => 'lt',
        'draft' => false,
        'publish_time' => now()->subMinute(),
    ]);
    $event = Calendar::factory()->create([
        'tenant_id' => $tenant->id,
        'title' => ['lt' => 'Nuorodos renginys', 'en' => 'Link event'],
        'date' => now()->addDay(),
        'end_date' => now()->addDay()->addHour(),
        'is_draft' => false,
        'is_remote' => true,
    ]);

    $page = visitPublicSubdomain('www', '/login');
    disableServiceWorker($page);
    ignoreResizeObserverLoopErrors($page);
    URL::forceRootUrl($page->script('location.origin'));
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => InstitutionActivityNotification::class,
        'data' => new InstitutionActivityNotification(new Collection([$request]))->toArray($user),
    ]);

    $page->click('button:has-text("Prisijungti el. paštu")');
    $page->fill('#email', $user->email)->fill('#password', 'password');
    $page->click('button[type="submit"]');
    waitForInertiaRender($page, '[data-slot=admin-shell]');

    $page->click('[data-tour=notifications-indicator]');
    $page->assertPresent('[data-slot=notification-primary-action]');
    expect($page->script('new URL(document.querySelector("[data-slot=notification-primary-action]").href).origin === location.origin'))->toBeTrue();
    $page->click('[data-slot=notification-primary-action]');

    $page->assertPathIs('/atsakymas/'.$request->id)
        ->assertPresent('form[action*="/atsakymas/"]')
        ->assertNoJavaScriptErrors();
    expect($request->fresh()->answer)->toBeNull()
        ->and($notification->fresh()->read_at)->not->toBeNull();

    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot=site-content] [data-slot=news-card]');
    $page->script('window.adminLinkProbe = true');
    $page->click('[data-slot=site-content] [data-slot=news-card]');
    waitForInertiaRender($page, 'h1:has-text("Nuorodos naujiena")');

    $page->assertSee($news->title)->assertNoJavaScriptErrors();
    expect($page->script('window.adminLinkProbe === undefined'))->toBeTrue();

    $page->navigate('/lt/naujienos');
    waitForInertiaRender($page, '[data-slot=news-card]');
    $page->script('window.publicLinkProbe = true');
    $page->click('a[href*="'.$news->permalink.'"]');
    waitForInertiaRender($page, 'h1:has-text("Nuorodos naujiena")');

    $page->assertNoJavaScriptErrors();
    expect($page->script('window.publicLinkProbe'))->toBeTrue();

    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot=site-content] [data-slot=event-card] a');
    $page->script('window.adminLinkProbe = true');
    $page->click('[data-slot=site-content] [data-slot=event-card] a');
    waitForInertiaRender($page, 'h1:has-text("Nuorodos renginys")');

    $page->assertSee($event->getTranslation('title', 'lt'))->assertNoJavaScriptErrors();
    expect($page->script('window.adminLinkProbe === undefined'))->toBeTrue();
});
