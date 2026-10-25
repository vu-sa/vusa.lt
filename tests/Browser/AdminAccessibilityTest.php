<?php

use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('applies reader preferences from the admin appearance menu', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()), '/mano/institutions');
    waitForInertiaRender($page);

    $initialSize = (float) $page->script('getComputedStyle(document.documentElement).fontSize');
    $initialBorder = $page->script('getComputedStyle(document.documentElement).getPropertyValue("--border")');

    $page->click('[data-slot="account-menu-trigger"]');
    $page->page()->locator('[data-slot="appearance-settings-trigger"]')->hover();
    $page->click('[data-slot="accessibility-settings-open"]');
    waitForInertiaRender($page, '[role=dialog] [data-slot="accessibility-settings"]');

    $page->click('[aria-label="Padidinti teksto dydį"]');
    expect((float) $page->script('getComputedStyle(document.documentElement).fontSize'))->toBeGreaterThan($initialSize);

    $page->click('[data-slot="accessibility-settings"] [data-preference="contrast"]');
    expect($page->script('getComputedStyle(document.documentElement).getPropertyValue("--border")'))->not->toBe($initialBorder);

    $page->click('[data-slot="accessibility-settings"] [data-preference="underline-links"]');
    expect($page->script('getComputedStyle(document.querySelector("a")).textDecorationLine'))->toContain('underline');

    $page->click('[data-slot="accessibility-settings"] [data-preference="reduce-motion"]');
    $page->assertAttribute('[data-preference="reduce-motion"]', 'aria-checked', 'true');
    expect($page->script('document.documentElement.classList.contains("reduce-motion")'))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

it('returns keyboard focus to the profile settings button when the dialog closes', function (): void {
    $page = loginAsAdmin(makeUser(Tenant::query()->first()), '/mano/profile');
    waitForInertiaRender($page, '[data-slot="profile-accessibility-trigger"]');

    $page->page()->locator('[data-slot="profile-accessibility-trigger"]')->press('Enter');
    waitForInertiaRender($page, '[data-slot="admin-accessibility-dialog"]');
    $page->click('[data-preference="reduce-motion"]');

    docsScreenshot($page, 'accessibility-settings', selector: '[data-slot="admin-accessibility-dialog"]');
    docsScreenshot($page, 'v3-accessibility-settings', selector: '[data-slot="admin-accessibility-dialog"]', highlights: ['[data-preference="reduce-motion"]']);

    if (env('DOCS_SCREENSHOTS')) {
        foreach ([390, 820, 1180, 1440] as $width) {
            foreach (['light', 'dark'] as $theme) {
                $page->resize($width, 900);
                $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
                $page->screenshot(fullPage: false, filename: "a11y-profile-{$width}-{$theme}");
            }
        }
    }

    $page->page()->locator('[data-preference="reduce-motion"]')->press('Escape');
    $page->page()->waitForSelector('[data-slot="admin-accessibility-dialog"]', ['state' => 'detached']);
    $page->page()->waitForSelector('[data-slot="profile-accessibility-trigger"]:focus');

    $page->click('[data-slot="profile-accessibility-trigger"]');
    $page->assertAttribute('[data-preference="reduce-motion"]', 'aria-checked', 'true');
    $page->click('[data-slot="admin-accessibility-dialog"] [data-slot="dialog-close"]');
    $page->page()->waitForSelector('[data-slot="profile-accessibility-trigger"]:focus');
    $page->assertNoJavaScriptErrors();
});

it('dismisses mobile navigation popups before the menu and restores its trigger focus', function (): void {
    $page = visitPublicSubdomain('www', '/lt');
    $page->resize(390, 844);

    foreach (['accessibility-menu-trigger' => 'accessibility-menu', 'locale-button' => 'locale-menu'] as $trigger => $popup) {
        $page->click('[data-slot="mobile-navigation-trigger"]');
        waitForInertiaRender($page, '[data-slot="mobile-navigation"]');
        $page->click('[data-slot="mobile-navigation"] [data-slot="'.$trigger.'"]');
        waitForInertiaRender($page, '[data-slot="'.$popup.'"]');

        $page->page()->locator('[data-slot="'.$popup.'"]')->press('Escape');
        $page->page()->waitForSelector('[data-slot="'.$popup.'"]', ['state' => 'detached']);
        $page->assertPresent('[data-slot="mobile-navigation"]');

        $page->page()->locator('[data-slot="mobile-navigation"] button')->first()->press('Escape');
        $page->page()->waitForSelector('[data-slot="mobile-navigation"]', ['state' => 'detached']);
        $page->page()->waitForSelector('[data-slot="mobile-navigation-trigger"]:focus');
    }

    $page->assertNoJavaScriptErrors();
});

it('keeps the background still and releases navigation when switching to desktop', function (): void {
    $page = visitPublicSubdomain('www', '/lt');
    $page->script('document.querySelector("main").style.minHeight = "200vh"');

    foreach ([390, 820] as $width) {
        $page->resize($width, 900);
        $page->script('window.scrollTo(0, 0)');
        $page->click('[data-slot="mobile-navigation-trigger"]');
        waitForInertiaRender($page, '[data-slot="mobile-navigation"]');
        $before = $page->script('window.scrollY');
        $page->page()->locator('[data-slot="mobile-navigation"] button')->first()->press('PageDown');
        $page->wait(0.25);
        expect($page->script('window.scrollY'))->toBe($before);

        $page->resize(1180, 900);
        $page->page()->waitForSelector('[data-slot="mobile-navigation"]', ['state' => 'detached']);
        $page->page()->waitForSelector('header a:focus');
        $page->click('[data-slot="accessibility-menu-trigger"]:visible');
        waitForInertiaRender($page, '[data-slot="accessibility-menu"]');
        $page->page()->locator('[data-slot="accessibility-menu"]')->press('Escape');
    }

    $page->assertNoJavaScriptErrors();
});

it('changes hero slides immediately without autoplay across responsive layouts', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $contentPage = Page::factory()->for($tenant)->active()->create([
        'title' => 'Judesio patikra', 'lang' => 'lt', 'permalink' => 'motion-check',
    ]);
    $contentPage->content->parts()->firstOrFail()->update([
        'type' => 'hero-carousel',
        'json_content' => array_map(fn (string $title): array => [
            'title' => $title, 'description' => '', 'imageSrc' => '', 'imageAlt' => '',
            'align' => 'start', 'buttons' => [],
        ], ['Pirma skaidrė', 'Antra skaidrė', 'Trečia skaidrė']),
        'options' => ['autoplay' => true, 'autoplayDelay' => 100, 'height' => 'sm', 'showArrows' => true, 'showIndicators' => true],
    ]);

    $page = visitPublicSubdomain('www', '/lt/motion-check');
    $page->resize(1440, 900);
    $page->click('[data-slot="accessibility-menu-trigger"]:visible');
    $page->click('[data-preference="reduce-motion"]');
    $page->page()->locator('[data-slot="accessibility-menu"]')->press('Escape');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            $previous = $page->script('document.querySelector("[data-slot=hero-carousel] button[aria-current=true]").getAttribute("aria-label")');
            $page->click('[data-slot="hero-carousel-next"]');
            $transforms = $page->script(<<<'JS'
                new Promise(resolve => {
                    const track = document.querySelector('[data-slot="hero-carousel"] [data-slot="carousel-content"] > div');
                    const frames = [];
                    const sample = () => {
                        frames.push(track.style.transform);
                        if (frames.length === 3) resolve(frames);
                        else requestAnimationFrame(sample);
                    };
                    requestAnimationFrame(sample);
                })
                JS);
            expect(array_unique($transforms))->toHaveCount(1);
            $selected = $page->script('document.querySelector("[data-slot=hero-carousel] button[aria-current=true]").getAttribute("aria-label")');
            expect($selected)->not->toBe($previous);
            $page->wait(0.25);
            expect($page->script('document.querySelector("[data-slot=hero-carousel] button[aria-current=true]").getAttribute("aria-label")'))->toBe($selected);
            $page->page()->locator('[data-slot="hero-carousel"] [data-slot="carousel"]')->press('ArrowLeft');
            expect($page->script('document.querySelector("[data-slot=hero-carousel] button[aria-current=true]").getAttribute("aria-label")'))->toBe($previous);
        }
    }

    $page->assertNoJavaScriptErrors();
});
