<?php

/**
 * Tenant scoping lives in the policies (ModelAuthorizer); there is no global query scope.
 * The full action × scope matrix is tests/Feature/Permissions/PermissionScopeMatrixTest.php.
 */

use App\Models\News;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantA = Tenant::factory()->create(['type' => 'padalinys', 'alias' => 'tenant-a']);
    $this->tenantB = Tenant::factory()->create(['type' => 'padalinys', 'alias' => 'tenant-b']);

    // Create admin users with appropriate roles for news management
    $this->adminA = makeUser($this->tenantA);
    $this->adminA->duties()->first()->assignRole('Komunikacijos koordinatorius');

});

describe('tenant data isolation', function (): void {
    test('users can only access data from their own tenant', function (): void {
        // Create news for each tenant
        $newsA = News::factory()->create(['tenant_id' => $this->tenantA->id]);
        $newsB = News::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Live news are read through the scoped Typesense key (TypesenseScopedKeyServiceTest);
        // the database path the page still uses is the trash, so isolate that.
        $newsA->delete();
        $newsB->delete();

        $newsIds = collect(asUser($this->adminA)
            ->getJson(route('api.v1.admin.trash.index', ['collection' => 'news']))
            ->assertOk()
            ->json('data.items'))->pluck('id');

        expect($newsIds)->toContain((string) $newsA->id)
            ->not->toContain((string) $newsB->id);

        asUser($this->adminA)->get(route('news.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('deletedCount', 1));
    });

    test('users cannot access other tenant resources via direct URLs', function (): void {
        $newsB = News::factory()->create(['tenant_id' => $this->tenantB->id]);

        asUser($this->adminA)->get(route('news.edit', $newsB))->assertForbidden();
    });

    test('users cannot update resources from other tenants', function (): void {
        $newsB = News::factory()->create(['tenant_id' => $this->tenantB->id]);

        asUser($this->adminA)->put(route('news.update', $newsB), [
            'title' => ['lt' => 'Unauthorized update', 'en' => 'Unauthorized update'],
        ])->assertForbidden();

        $newsB->refresh();
        expect($newsB->title)->not->toBe(['lt' => 'Unauthorized update', 'en' => 'Unauthorized update']);
    });

    test('tenant-scoped permissions work correctly', function (): void {
        $newsA = News::factory()->create(['tenant_id' => $this->tenantA->id]);
        $newsB = News::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Admin A can update news from their tenant
        expect($this->adminA->can('update', $newsA))->toBeTrue();

        // Admin A cannot update news from other tenant
        expect($this->adminA->can('update', $newsB))->toBeFalse();
    });

    test('super admin can access all tenant data', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $newsA = News::factory()->create(['tenant_id' => $this->tenantA->id]);
        $newsB = News::factory()->create(['tenant_id' => $this->tenantB->id]);

        expect($superAdmin->can('update', $newsA))->toBeTrue()
            ->and($superAdmin->can('update', $newsB))->toBeTrue();
    });
});

describe('cross-tenant data prevention', function (): void {
    test('mass assignment cannot bypass tenant restrictions', function (): void {
        $newsA = News::factory()->create(['tenant_id' => $this->tenantA->id]);

        // Try to change tenant_id through mass assignment
        asUser($this->adminA)->put(route('news.update', $newsA), [
            'title' => 'Updated',
            'tenant_id' => $this->tenantB->id, // This should be ignored
        ])->assertRedirect();

        $newsA->refresh();
        expect($newsA->tenant_id)->toBe($this->tenantA->id); // Should remain unchanged
    });

    test('search results are tenant-scoped', function (): void {
        News::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'title' => 'Tenant A News',
        ]);

        News::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'title' => 'Tenant B News',
        ]);

        News::query()->each(fn (News $news) => $news->delete());

        $results = collect(asUser($this->adminA)
            ->getJson(route('api.v1.admin.trash.index', ['collection' => 'news', 'search' => 'News']))
            ->assertOk()
            ->json('data.items'));

        expect($results)->not->toBeEmpty()
            ->and($results->every(fn (array $result): bool => $result['tenant_id'] === $this->tenantA->id))->toBeTrue();
    });
});
