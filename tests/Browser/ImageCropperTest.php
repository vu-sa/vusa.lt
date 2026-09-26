<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('changes crop proportions and fits phone and tablet screens', function (): void {
    $user = makeUser(Tenant::query()->first());
    $user->update(['profile_photo_path' => '/images/placeholders/foto1.jpg']);

    $page = loginAsAdmin($user);
    $page->navigate('/mano/profile');
    waitForInertiaRender($page);
    $page->click('button:has-text("Apkirpti")');
    $page->assertVisible('[data-slot="image-cropper"]');
    $page->page()->waitForSelector('button:has-text("Išsaugoti kadrą"):not([disabled])');
    expect($page->script('document.querySelector("cropper-selection").width / document.querySelector("cropper-image").getBoundingClientRect().width'))
        ->toBeGreaterThan(0.5);

    foreach ([390, 820, 1440] as $width) {
        $page->resize($width, 900);
        $page->screenshot(fullPage: false, filename: 'cropper-'.$width.'-light');

        expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue()
            ->and($page->script('document.querySelector("[data-slot=dialog-content]").getBoundingClientRect().bottom <= window.innerHeight'))->toBeTrue();
    }

    $page->resize(390, 900);
    $page->script('document.documentElement.classList.add("dark")');
    $page->screenshot(fullPage: false, filename: 'cropper-390-dark');

    $page->click('button:has-text("16:9")');
    expect($page->script('document.querySelector("cropper-selection").aspectRatio'))->toBe(16 / 9);
    $page->click('button:has-text("Laisvai")');
    expect($page->script('document.querySelector("cropper-selection").aspectRatio'))->toBe(0);

    $page->assertNoJavaScriptErrors();
});
