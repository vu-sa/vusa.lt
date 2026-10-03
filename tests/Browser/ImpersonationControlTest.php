<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the impersonation control visible and square on desktop and phone', function (): void {
    config(['app.env' => 'local']);

    $user = User::factory()->create();
    $user->assignRole(config('permission.super_admin_role_name'));

    $page = loginAsAdmin($user);

    foreach ([1440, 1180, 820, 390] as $width) {
        $page->resize($width, 900);
        $page->assertPresent('[data-slot=impersonation-launcher]');

        $geometry = $page->script('(() => {
            const button = document.querySelector("[data-slot=impersonation-launcher] button");
            return {
                radius: getComputedStyle(button).borderTopLeftRadius,
                height: button.getBoundingClientRect().height,
                fits: button.getBoundingClientRect().right <= innerWidth,
            };
        })()');

        expect($geometry['radius'])->toBe('0px')
            ->and($geometry['fits'])->toBeTrue();

        if ($width === 390) {
            expect($geometry['height'])->toBeGreaterThanOrEqual(44);
        }
    }

    $page->click('[data-slot=impersonation-bar-close]');
    expect($page->script('document.querySelector("[data-slot=impersonation-launcher]") === null'))->toBeTrue();

    $page->navigate('/mano');
    waitForInertiaRender($page);
    $page->assertPresent('[data-slot=impersonation-launcher]');

    $page->assertNoJavaScriptErrors();
});

it('aligns the active impersonation bar with the admin shell', function (): void {
    config(['app.env' => 'local']);

    $actor = User::factory()->create();
    $actor->assignRole(config('permission.super_admin_role_name'));
    User::factory()->create(['name' => 'Akvilė Banytė', 'email' => 'akvile@example.com']);

    $page = loginAsAdmin($actor);
    $page->click('[data-slot=impersonation-launcher] [data-slot=popover-trigger]');
    $page->fill('input[placeholder="Ieškok pagal vardą ar el. paštą…"]', 'Akvilė');
    $page->assertSee('Akvilė Banytė');
    $page->click('button:has-text("Akvilė Banytė")');
    $page->assertPresent('[data-slot=impersonation-status]');

    $geometry = $page->script('(() => {
        const content = document.querySelector("[data-slot=impersonation-status] > div");
        const shell = document.querySelector("[data-slot=shell-top-bar] > div");
        const button = content.querySelector("button");
        return {
            aligned: content.getBoundingClientRect().left === shell.getBoundingClientRect().left,
            buttonHeight: button.getBoundingClientRect().height,
            radius: getComputedStyle(button).borderTopLeftRadius,
        };
    })()');

    expect($geometry['aligned'])->toBeTrue()
        ->and($geometry['buttonHeight'])->toBe(32)
        ->and($geometry['radius'])->toBe('0px');

    $page->assertNoJavaScriptErrors();
});
