<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('opens a create sheet with the keyboard from another page and from its own collection', function (): void {
    config(['session.domain' => null, 'session.secure' => false]);
    $actor = makeAdminUser(Tenant::query()->firstOrFail());
    $page = loginAsAdmin($actor, '/mano/tenants', width: 1440);
    $search = '[data-slot="command"] input';
    $command = '[data-slot="command-item"]:has([data-slot="create-command-icon"])';
    $sheet = '[data-slot="sheet-form"]';

    foreach ([1440, 390] as $width) {
        $page->resize($width, 900);
        $page->script(sprintf('document.documentElement.classList.toggle("dark", %s)', $width === 390 ? 'true' : 'false'));
        $page->page()->locator('body')->press('Control+k');
        waitForInertiaRender($page, $search);
        $page->type($search, 'Nauja žyma');
        waitForInertiaRender($page, $command);
        $page->keys($search, 'ArrowDown');
        expect($page->script('document.activeElement?.matches(\'[data-slot="command-item"]\')'))->toBeTrue();
        $page->keys($command, 'Enter');

        waitForInertiaRender($page, $sheet);
        $page->assertPathIs('/mano/tags');
        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);

        $page->keys($sheet, 'Escape');
        $page->assertMissing($sheet);
        expect($page->script('location.search'))->toBe('');
    }
});
