<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('starts the public site in light mode even when the system prefers dark', function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    pest()->browser()->inDarkMode();

    $publicPage = visitPublicSubdomain('www', '/lt/naujienos');

    expect($publicPage->script('localStorage.getItem("vueuse-color-scheme")'))->toBe('light')
        ->and($publicPage->script('document.documentElement.classList.contains("dark")'))->toBeFalse();
});

it('starts the admin app in light mode even when the system prefers dark', function (): void {
    pest()->browser()->inDarkMode();

    $user = makeUser(Tenant::query()->first());
    $page = loginAsAdmin($user);
    $page->script('localStorage.removeItem("vueuse-color-scheme")');
    $page->navigate('/mano');
    waitForInertiaRender($page);

    expect($page->script('localStorage.getItem("vueuse-color-scheme")'))->toBe('light')
        ->and($page->script('document.documentElement.classList.contains("dark")'))->toBeFalse();
});

it('keeps a saved dark choice', function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $page = visitPublicSubdomain('www', '/lt/naujienos');
    $page->script('localStorage.setItem("vueuse-color-scheme", "dark")');
    $page->navigate('/lt/naujienos');
    waitForInertiaRender($page);

    expect($page->script('localStorage.getItem("vueuse-color-scheme")'))->toBe('dark')
        ->and($page->script('document.documentElement.classList.contains("dark")'))->toBeTrue();
});
