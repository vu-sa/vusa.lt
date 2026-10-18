<?php

use App\Models\Tenant;
use App\Services\OnboardingChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('saves checklist decisions through the built UI and fits the supported widths', function (): void {
    $user = makeTenantUserWithRole('Išteklių administratorius', Tenant::main());
    $page = loginAsAdmin($user);
    $page->navigate('/mano/onboarding');
    waitForInertiaRender($page, '[data-slot=checklist-items]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
            $page->screenshot(fullPage: false, filename: 'onboarding-'.$width.($dark ? '-dark' : '-light'));
            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
        }
    }

    $page->script('document.documentElement.classList.remove("dark")');
    $page->resize(1440, 900);
    docsScreenshot($page, 'onboarding');
    docsScreenshot($page, 'v3-onboarding', highlights: ['[data-slot=checklist-progress]', '[data-item=access]']);

    $page->click('[data-testid=complete-access]')->assertSee('Peržiūrėta 1 iš 6 žingsnių');
    expect($user->fresh()->onboarding_progress[OnboardingChecklist::KEY]['items']['access']['status'])->toBe('done');

    $page->click('[data-item=push] summary');
    $page->click('[data-testid=skip-push]')->assertSee('Peržiūrėta 2 iš 6 žingsnių');
    expect($user->fresh()->onboarding_progress[OnboardingChecklist::KEY]['items']['push']['status'])->toBe('skipped');

    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot=onboarding-summary]');
    $page->assertSee('Peržiūrėta 2 iš 6 žingsnių')->assertNoJavaScriptErrors();
});
