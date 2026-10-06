<?php

use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('shows a representative the delivery options for every notification type within the viewport', function (): void {
    $this->seed(DocsSeeder::class);

    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano/profile/notifications');
    waitForInertiaRender($page, '[data-slot=form-page]');

    // Labels resolve once the translations arrive; a raw key here means a label was read too early.
    $rawKey = 'document.body.innerText.includes("notifications.preferences.")';
    $deadline = microtime(true) + 5;

    while ($page->script($rawKey) && microtime(true) < $deadline) {
        usleep(100_000);
    }

    expect($page->script($rawKey))->toBeFalse();

    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
    }

    $page->resize(1440, 1000);
    docsScreenshot($page, 'notification-preferences');
    docsScreenshot($page, 'v3-notification-settings', highlights: [
        '[data-testid=email-select]',
        '[data-testid=push-toggle]',
        '[data-testid=form-page-aside] > :first-child',
    ]);

    $page->assertNoJavaScriptErrors();
});
