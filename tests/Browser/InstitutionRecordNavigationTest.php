<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps institution sections reachable on phones and desktops', function () {
    Role::findOrCreate(config('permission.super_admin_role_name'), 'web');
    $user = User::factory()->create();
    $user->assignRole(config('permission.super_admin_role_name'));
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution)->create();
    Dutiable::factory()->forDuty($duty)->active()->create();
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
    $page->assertNoJavaScriptErrors();
});
