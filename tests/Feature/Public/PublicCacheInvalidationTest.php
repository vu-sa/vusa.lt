<?php

use App\Models\Calendar;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->main = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $this->other = Tenant::factory()->create(['alias' => 'kitas', 'type' => 'padalinys']);

    $this->mainPage = Page::factory()->create(['tenant_id' => $this->main->id, 'lang' => 'lt', 'is_active' => true, 'title' => 'Pradinis']);
    $this->otherPage = Page::factory()->create(['tenant_id' => $this->other->id, 'lang' => 'lt', 'is_active' => true, 'title' => 'Pradinis']);

    $this->titleOf = function (Page $page, Tenant $tenant) {
        // Routes keep their controller between requests in one test, and PublicController
        // resolves its tenant in the constructor.
        collect(Route::getRoutes()->getRoutes())->each->flushController();

        return $this->get(route('page', ['subdomain' => $tenant->subdomain(), 'lang' => 'lt', 'permalink' => $page->permalink]))
            ->assertOk()
            ->inertiaProps('page.title');
    };

    // Cache both, then change the titles behind the cache's back (no model events).
    ($this->titleOf)($this->mainPage, $this->main);
    ($this->titleOf)($this->otherPage, $this->other);
    DB::table('pages')->whereIn('id', [$this->mainPage->id, $this->otherPage->id])->update(['title' => 'Pakeistas']);
});

test('saving a page refreshes its own tenant pages and leaves other tenants cached', function (): void {
    Page::factory()->create(['tenant_id' => $this->main->id, 'lang' => 'lt']);

    expect(($this->titleOf)($this->mainPage, $this->main))->toBe('Pakeistas')
        ->and(($this->titleOf)($this->otherPage, $this->other))->toBe('Pradinis');
});

test('saving a calendar event leaves page caches alone', function (): void {
    Calendar::factory()->create(['tenant_id' => $this->main->id]);

    expect(($this->titleOf)($this->mainPage, $this->main))->toBe('Pradinis')
        ->and(($this->titleOf)($this->otherPage, $this->other))->toBe('Pradinis');
});
