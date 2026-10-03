<?php

use App\Models\Banner;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('renders partner banners in a ruled grid across viewports and themes without overflow', function (): void {
    $tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    Banner::factory()->for($tenant)->withoutLogo()->count(7)->create([
        'is_active' => 1,
        'lang' => 'lt',
    ]);

    $page = visitPublicSubdomain('www', '/lt/naujienos');
    $page->assertPresent('[data-slot="ruled-grid"]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);

        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');

            expect($page->script('document.querySelectorAll("[data-slot=\"ruled-grid\"] > *").length'))->toBe(7)
                ->and($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
        }
    }

    $page->assertNoJavaScriptErrors();
});

it('renders quick-access navigation tiles without horizontal overflow', function (): void {
    $user = makeAdminUser(Tenant::query()->first());
    $page = loginAsAdmin($user);
    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot="navigation-tiles"]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.querySelectorAll("[data-slot=\"navigation-tiles\"] > *").length'))->toBeGreaterThan(0)
            ->and($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    }

    $page->assertNoJavaScriptErrors();
});

it('renders institution contact details in a grid without horizontal overflow', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create([
        'name' => ['lt' => 'Studentų atstovybė', 'en' => 'Student Representation'],
        'description' => ['lt' => '<p>Kontaktai</p>', 'en' => '<p>Contacts</p>'],
        'address' => ['lt' => 'Universiteto g. 3', 'en' => 'Universiteto St. 3'],
        'working_hours' => ['lt' => '9–17 val.', 'en' => '9 am–5 pm'],
        'phone' => '+37060000000',
        'email' => 'contact@example.com',
        'website' => 'https://example.com',
        'facebook_url' => 'https://facebook.com/example',
        'instagram_url' => 'https://instagram.com/example',
        'image_url' => null,
    ]);

    $page = visitPublicSubdomain('www', '/lt/kontaktai/id/'.$institution->id);
    $page->assertSee('Facebook')->assertSee('Instagram');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.querySelectorAll("[data-slot=\"ruled-grid\"] > *").length'))->toBe(7)
            ->and($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
    }

    $page->assertNoJavaScriptErrors();
});
