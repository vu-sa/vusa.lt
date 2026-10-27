<?php

use App\Models\InstitutionActivityRequest;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('opens personal requests from home and fits the supported widths in both themes', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $institution = $user->duties()->firstOrFail()->institution;
    InstitutionActivityRequest::factory()->create([
        'recipient_id' => $user->id,
        'institution_id' => $institution->id,
        'period_end' => today(),
        'note' => 'Ar vyko posėdžiai? Papildyk institucijos veiklos informaciją už nurodytą laikotarpį.',
    ]);
    InstitutionActivityRequest::factory()->answered()->create([
        'recipient_id' => $user->id,
        'institution_id' => $institution->id,
    ]);

    $page = loginAsAdmin($user, '/mano/profile/activity-requests', 390, 900);
    waitForInertiaRender($page, '[data-slot=user-activity-request-history]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
            $page->screenshot(fullPage: false, filename: 'user-activity-requests-'.$width.($dark ? '-dark' : '-light'));
            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
        }
    }

    navigateTo($page, '/mano');
    waitForInertiaRender($page, '[data-slot=home-activity-requests]');
    $page->click('[data-slot=home-activity-requests] a[href*="/profile/activity-requests"]')
        ->assertPathIs('/mano/profile/activity-requests');
    waitForInertiaRender($page, '[data-slot=user-activity-request-history]');
    $page->assertNoJavaScriptErrors();
});
