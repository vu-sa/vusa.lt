<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps institution sections reachable on phones and desktops', function (): void {
    Role::findOrCreate(config('permission.super_admin_role_name'), 'web');
    $user = User::factory()->create();
    $user->assignRole(config('permission.super_admin_role_name'));
    $institution = Institution::factory()->create();
    foreach (range(1, 5) as $order) {
        $duty = Duty::factory()->for($institution)->create(['order' => $order]);
        Dutiable::factory()->forDuty($duty)->active()->count($order === 1 ? 8 : 1)->create();
    }
    foreach ([-3, -2, -1, 1] as $days) {
        $meeting = Meeting::factory()->create(['start_time' => now()->addDays($days)]);
        $meeting->institutions()->attach($institution);
    }
    $page = loginAsAdmin($user, "/mano/institutions/{$institution->id}", 390, 844);
    waitForInertiaRender($page, '[data-slot="record-title"]');
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
        if ($width < 768) {
            $page->select('[data-slot="record-section-switcher"] select', 'comments');
        } else {
            $page->click('[role="tab"][data-section="comments"]');
        }
        $page->assertVisible('[id^="record-panel-"][id$="-comments"]');
        if ($page->script('document.querySelector("[data-testid=composer-launcher]") !== null')) {
            $page->click('[data-testid=composer-launcher]');
        }
        $page->type('[id^="record-panel-"][id$="-comments"] [contenteditable=true]', "Draft {$width}");
        if ($width < 768) {
            $page->select('[data-slot="record-section-switcher"] select', 'overview');
        } else {
            $page->click('[role="tab"][data-section="overview"]');
        }
        $page->assertVisible('[data-slot="institution-overview"]');
        $page->screenshot(filename: "institution-overview-{$width}");
        if ($width < 768) {
            $page->select('[data-slot="record-section-switcher"] select', 'comments');
        } else {
            $page->click('[role="tab"][data-section="comments"]');
        }
        $page->assertSee("Draft {$width}");
        $page->script("document.documentElement.classList.add('dark')");
        $page->screenshot(filename: "institution-record-dark-{$width}");
        $page->script("document.documentElement.classList.remove('dark')");
        $page->screenshot(filename: "institution-record-{$width}");
    }
    $page->resize(1440, 900);
    $page->click('[role="tab"][data-section="duties"]');
    $page->assertPresent('[data-slot="institution-duty-row"]');
    $page->click('button:has-text("Keisti tvarką")');
    $page->assertPresent('[data-slot="sortable-row-list"] .handle');
    $page->screenshot(filename: 'institution-duty-reorder');
    $before = $page->script("document.querySelector('[data-slot=sortable-row-list] a').href");
    $page->page()->locator('[data-slot="sortable-row-list"] li:nth-child(1) .handle')->dragTo($page->page()->locator('[data-slot="sortable-row-list"] li:nth-child(3) .handle'), ['steps' => 20]);
    $page->assertScript("document.querySelector('[data-slot=sortable-row-list] a').href !== ".json_encode($before));
    $page->click('[data-slot="institution-duties-section"] button:has-text("Atšaukti")');
    $page->click('[data-slot="record-facts"] button[data-slot="users-avatar-group"]');
    $page->assertVisible('[data-slot="users-avatar-group-popover"]');
    $page->screenshot(filename: 'institution-people-popover');
    $page->assertNoJavaScriptErrors();
});
