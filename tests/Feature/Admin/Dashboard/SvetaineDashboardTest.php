<?php

use App\Models\Calendar;
use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create related test data
    $this->page = Page::factory()->for($this->tenant)->create();
    $this->news = News::factory()->for($this->tenant)->create();
    $this->quickLink = QuickLink::factory()->for($this->tenant)->create();
    $this->resource = Resource::factory()->for($this->tenant)->create();
});

describe('svetaine dashboard', function (): void {
    test('admin can access svetaine dashboard', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.svetaine'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->has('tenants')
                ->has('providedTenant')
            );
    });

    test('regular user cannot access svetaine dashboard', function (): void {
        asUser($this->user)
            ->get(route('dashboard.svetaine'))
            ->assertStatus(403);
    });

    test('svetaine dashboard includes tenant data', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.svetaine', ['tenant_id' => $this->tenant->id]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->where('providedTenant.id', $this->tenant->id)
                ->has('providedTenant.alias')
                ->has('providedTenant.shortname')
            );
    });

    /**
     * The content counters this page used to show were replaced by the Umami traffic
     * section, which is fetched client-side. Shipping the collections anyway would mean
     * loading every page, news item, quick link and a year of calendar entries on each
     * dashboard view for nothing.
     */
    test('svetaine dashboard does not ship unused content collections', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.svetaine', ['tenant_id' => $this->tenant->id]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->missing('providedTenant.pages')
                ->missing('providedTenant.news')
                ->missing('providedTenant.quick_links')
                ->missing('providedTenant.calendar')
            );
    });
});

describe('svetaine counts', function (): void {
    test('counts the selected tenant\'s content, drafts apart from the total', function (): void {
        News::factory()->for($this->tenant)->count(2)->create(['draft' => true]);
        News::factory()->for($this->tenant)->create(['draft' => false]);
        Calendar::factory()->for($this->tenant)->create(['is_draft' => true]);
        News::factory()->for(Tenant::query()->where('id', '!=', $this->tenant->id)->first())->create(['draft' => true]);

        asUser($this->admin)
            ->get(route('dashboard.svetaine', ['tenant_id' => $this->tenant->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.newsDrafts', 2)
                ->where('counts.news', 4)
                ->where('counts.calendarDrafts', 1)
                ->where('counts.pages', fn ($pages) => $pages >= 1)
            );
    });
});

describe('svetaine tenant isolation', function (): void {
    beforeEach(function (): void {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
    });

    test('user cannot access other tenant svetaine data directly', function (): void {
        asUser($this->admin)
            ->get(route('dashboard.svetaine', ['tenant_id' => $this->otherTenant->id]))
            ->assertStatus(200) // They can access but should see filtered data
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->where('tenants',
                    // Should not contain unauthorized tenants
                    fn ($tenants) => collect($tenants)->every(fn ($tenant) => $tenant['id'] === $this->tenant->id || $tenant['type'] === 'pagrindinis'))
            );
    });
});
