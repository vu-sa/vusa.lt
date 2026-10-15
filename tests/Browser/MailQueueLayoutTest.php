<?php

use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\Tenant;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps a populated mail queue within phone and desktop viewports', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $admin = makeAdminUser($tenant);
    $recipient = makeUser($tenant);

    NotificationDigestQueue::create([
        'user_id' => $recipient->id,
        'notification_class' => TaskAssignedNotification::class,
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => 'Nauja užduotis', 'body' => 'Užpildyk darbotvarkės klausimų informaciją', 'url' => '/mano'],
    ]);

    $page = loginAsAdmin($admin);
    $page->navigate('/mano/mail-queue');
    waitForInertiaRender($page, '[data-slot="collection-page"]');

    foreach (['light', 'dark'] as $theme) {
        $page->script($theme === 'dark'
            ? 'document.documentElement.classList.add("dark")'
            : 'document.documentElement.classList.remove("dark")');

        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900);

            // The collection switches from table to phone rows a few frames after a resize.
            for ($attempt = 0; $attempt < 20 && $page->script('document.documentElement.scrollWidth') > $width; $attempt++) {
                $page->wait(0.1);
            }

            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
        }
    }

    $page->resize(1440, 900);
    docsScreenshot($page, 'mail-queue');

    $page->assertNoJavaScriptErrors();
});
