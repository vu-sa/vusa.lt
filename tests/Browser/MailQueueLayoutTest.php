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
        'data' => ['title' => 'Pending task', 'body' => 'Review the task', 'url' => '/mano'],
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
            $page->script('new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
        }
    }

    $page->assertNoJavaScriptErrors();
});
