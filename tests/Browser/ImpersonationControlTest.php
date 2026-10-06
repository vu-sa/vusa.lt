<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the impersonation launcher visible across widths and dismissible until reload', function (): void {
    config(['app.env' => 'local']);

    $user = User::factory()->create();
    $user->assignRole(config('permission.super_admin_role_name'));

    $page = loginAsAdmin($user);

    foreach ([1440, 1180, 820, 390] as $width) {
        $page->resize($width, 900);
        $page->assertPresent('[data-slot=impersonation-launcher]');
        expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    }

    $page->click('[data-slot=impersonation-bar-close]');
    expect($page->script('document.querySelector("[data-slot=impersonation-launcher]") === null'))->toBeTrue();

    $page->navigate('/mano');
    waitForInertiaRender($page);
    $page->assertPresent('[data-slot=impersonation-launcher]');

    $page->assertNoJavaScriptErrors();
});

it('allows starting impersonation from the launcher popover', function (): void {
    config(['app.env' => 'local']);

    $actor = User::factory()->create();
    $actor->assignRole(config('permission.super_admin_role_name'));
    User::factory()->create(['name' => 'Akvilė Banytė', 'email' => 'akvile@example.com']);

    $page = loginAsAdmin($actor);
    $page->click('[data-slot=impersonation-launcher] [data-slot=popover-trigger]');
    waitForInertiaRender($page, '[data-slot=impersonation-search-input]');

    $page->fill('[data-slot=impersonation-search-input]', 'Akvilė');
    $page->assertSee('Akvilė Banytė');
    $page->click('button:has-text("Akvilė Banytė")');

    waitForInertiaRender($page, '[data-slot=impersonation-status]');
    $page->assertPresent('[data-slot=impersonation-status]');

    $page->assertNoJavaScriptErrors();
});
