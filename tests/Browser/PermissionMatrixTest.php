<?php

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('meeting scopes can be changed and saved with explanations at working widths in both themes', function (): void {
    $admin = makeAdminUser(Tenant::query()->firstOrFail());
    $role = Role::factory()->create();
    $role->givePermissionTo(['meetings.read.padalinys', 'meetings.create.own', 'meetings.update.own', 'meetings.delete.own']);
    $page = loginAsAdmin($admin, "/mano/responsibilities/{$role->id}");
    waitForInertiaRender($page, '[data-slot=permission-matrix]');
    $page->fill('[data-testid=matrix-search]', 'meetings');

    foreach (['light', 'dark'] as $theme) {
        $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 1000);
            $page->screenshotElement('[data-slot=permission-matrix]', filename: "permission-matrix-{$theme}-{$width}");
        }
    }

    $page->select('[data-testid=matrix-table] [data-cell="meetings.update"]', 'padalinys');
    $page->click('[data-testid=matrix-save]');
    $page->assertMissing('[data-testid=matrix-save]')->assertNoJavaScriptErrors();

    expect($role->fresh()->permissions->pluck('name'))->toContain('meetings.update.padalinys')->not->toContain('meetings.update.own');
});
