<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('opens concrete type collections and their forms without browser errors', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->navigate('/mano/types');
    waitForInertiaRender($page, '[data-slot=navigation-tiles]');
    $page->assertPresent('[data-tile=instituciju_tipai]')->assertPresent('[data-tile=pareigybiu_tipai]');
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    }
    $page->click('[data-tile=instituciju_tipai]');
    waitForInertiaRender($page, '[data-slot=collection-table]');
    $page->assertNoJavaScriptErrors();
    $page->navigate('/mano/dutyTypes/create');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertDontSee('rich-content.')->assertNoJavaScriptErrors();
    $page->resize(390, 844);
    $page->script('document.documentElement.classList.add("dark")');
    expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    $page->navigate('/mano/institutionTypes/create');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertNoJavaScriptErrors();
});
