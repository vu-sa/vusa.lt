<?php

use App\Models\QuickLink;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the signed-in English menu in English from its first browser render', function (): void {
    $tenant = Tenant::where('alias', 'vusa')->firstOrFail();
    $user = makeUser($tenant);
    QuickLink::factory()->create([
        'tenant_id' => $tenant->id, 'lang' => 'en', 'icon' => 'calendar-24-regular',
    ]);

    $page = visitPublicSubdomain('www', '/lt');
    $page->resize(1440, 900);
    app('url')->forceRootUrl($page->script('location.origin'));
    disableServiceWorker($page);
    $page->navigate($page->script('new URL("/login", location.href).href'));
    waitForInertiaRender($page);
    $page->click('button:has-text("Prisijungti el. paštu")')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('main button[type="submit"]');
    waitForInertiaRender($page, '[data-slot="admin-shell"]');
    // Pest reuses route controllers; the next request needs fresh constructor-shared props.
    app('router')->getRoutes()->getByName('home')->flushController();

    $page->page()->context()->addInitScript(<<<'JS'
        window.publicMenuRenders = [];
        new MutationObserver(() => {
            const header = document.querySelector('header');
            if (!header) return;
            window.publicMenuRenders.push({
                lithuanian: /Padaliniai|Mano VU SA/.test(header.textContent),
                english: header.textContent.includes('Units') && header.textContent.includes('My VU SR'),
                icon: Boolean(header.querySelector('a.plain svg path')),
            });
        }).observe(document, { childList: true, subtree: true, characterData: true });
        JS);

    $page->navigate($page->script('new URL("/en", location.href).href'));
    waitForInertiaRender($page, '[data-slot="header-wordmark"]');

    expect($page->script('window.publicMenuRenders[0]'))
        ->toBe(['lithuanian' => false, 'english' => true, 'icon' => true]);
    expect($page->script('window.publicMenuRenders.some(render => render.lithuanian)'))->toBeFalse();
    expect($page->script('JSON.parse(document.querySelector("script[data-page]").textContent).props.auth.user.id'))->toBe($user->id);
    expect($page->script('document.getElementById("app").hasAttribute("data-server-rendered")'))->toBeFalse();
    $page->assertNoJavaScriptErrors();
});
