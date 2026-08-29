<?php

use App\Enums\NotificationCategory;
use App\Models\NotificationDigestQueue;
use App\Models\Tenant;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The mail queue page reached the way an admin actually reaches it: by clicking the system
 * status card. Both the card link and the page's own bundle are client-side only, so a Feature
 * request test proves the props and nothing about whether the page renders.
 */
beforeEach(function (): void {
    $this->admin = makeAdminUser(Tenant::query()->first());
    $this->recipient = makeUser(Tenant::query()->first());

    NotificationDigestQueue::create([
        'user_id' => $this->recipient->id,
        'notification_class' => TaskAssignedNotification::class,
        'category' => NotificationCategory::Task->value,
        'data' => ['title' => 'Priskirta užduotis', 'body' => 'Body', 'url' => '/test', 'icon' => '📌'],
    ]);
});

it('opens from the system status card and lists the pending digest', function (): void {
    $page = loginAsAdmin($this->admin);

    $page->navigate('/mano/system-status');
    waitForInertiaRender($page, 'a[href$="/mano/mail-queue"]');

    $page->click('a[href$="/mano/mail-queue"]');

    waitForInertiaRender($page, 'h1');

    $page->assertSee($this->recipient->name)
        ->assertSee('Priskirta užduotis')
        ->assertNoJavaScriptErrors();
});
