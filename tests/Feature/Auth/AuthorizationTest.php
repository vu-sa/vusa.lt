<?php

use App\Facades\Permission;
use App\Models\Content;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function createContent(): string
{
    $content = Content::create();
    $content->parts()->createMany([
        ['type' => 'text', 'json_content' => json_encode(['text' => 'Test content'])],
        ['type' => 'image', 'json_content' => json_encode(['url' => 'test.jpg'])],
    ]);
    $content->save();

    return $content->id;
}

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create([
        'type' => 'padalinys',
        'alias' => 'test-tenant',
    ]);

    $this->institution = Institution::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->duty = Duty::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    // ModelPermissionSeeder only seeds the padalinys/own scopes news actually allows;
    // the .all variants and the deliberately-bogus scope below don't exist yet. One
    // bulk upsert (matching ModelPermissionSeeder's own convention) instead of a
    // per-permission exists()-then-create() loop.
    App\Models\Permission::upsert(
        collect([
            'news.read.padalinys',
            'news.create.padalinys',
            'news.update.padalinys',
            'news.delete.padalinys',
            'news.update.own',
            'news.read.all',
            'news.create.all',
            'news.update.all',
            'news.delete.all',
            'unknown.permission.scope',
        ])->map(fn (string $name) => ['name' => $name, 'guard_name' => 'web'])->all(),
        ['name', 'guard_name']
    );

    $this->tenantAdminRole = Role::firstOrCreate(['name' => 'Tenant Admin', 'guard_name' => 'web']);
    $this->contentEditorRole = Role::firstOrCreate(['name' => 'Content Editor', 'guard_name' => 'web']);
    $this->communicationCoordinatorRole = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);

    $this->tenantAdminRole->givePermissionTo([
        'news.read.padalinys',
        'news.create.padalinys',
        'news.update.padalinys',
        'news.delete.padalinys',
    ]);

    $this->contentEditorRole->givePermissionTo([
        'news.read.padalinys',
        'news.create.padalinys',
        'news.update.own',
    ]);

    $this->communicationCoordinatorRole->givePermissionTo([
        'news.read.padalinys',
        'news.create.padalinys',
        'news.update.padalinys',
    ]);

    $this->superAdmin = User::factory()->create();
    $this->normalUser = makeUser($this->tenant); // Use makeUser to give normalUser a duty
    $this->tenantAdmin = User::factory()->create();

    $this->superAdmin->assignRole(config('permission.super_admin_role_name'));

    $this->duty->assignRole($this->tenantAdminRole);
    $this->tenantAdmin->duties()->attach($this->duty->id, ['start_date' => now()->subDay()]);

    $this->news = News::create([
        'title' => 'Test News',
        'short' => 'Test short description',
        'tenant_id' => $this->tenant->id,
        'content_id' => createContent(),
    ]);
});

test('super admin has all permissions', function (): void {
    expect(Permission::check('news.read.all', $this->superAdmin))->toBeTrue()
        ->and(Permission::check('news.create.all', $this->superAdmin))->toBeTrue()
        ->and(Permission::check('news.update.all', $this->superAdmin))->toBeTrue()
        ->and(Permission::check('news.delete.all', $this->superAdmin))->toBeTrue()
        ->and(Permission::check('unknown.permission.scope', $this->superAdmin))->toBeTrue();
});

test('tenant admin has tenant scoped permissions', function (): void {
    expect(Permission::check('news.read.padalinys', $this->tenantAdmin))->toBeTrue()
        ->and(Permission::check('news.create.padalinys', $this->tenantAdmin))->toBeTrue()
        ->and(Permission::check('news.update.padalinys', $this->tenantAdmin))->toBeTrue()
        ->and(Permission::check('news.delete.padalinys', $this->tenantAdmin))->toBeTrue()
        ->and(Permission::check('news.read.all', $this->tenantAdmin))->toBeFalse();
});

test('normal user has no permissions', function (): void {
    expect(Permission::check('news.read.padalinys', $this->normalUser))->toBeFalse()
        ->and(Permission::check('news.create.padalinys', $this->normalUser))->toBeFalse()
        ->and(Permission::check('news.update.padalinys', $this->normalUser))->toBeFalse()
        ->and(Permission::check('news.delete.padalinys', $this->normalUser))->toBeFalse();
});

test('tenant scoped permissions only apply to correct tenant', function (): void {
    $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
    $otherContent = createContent();
    $otherNews = News::create([
        'title' => 'Other Tenant News',
        'short' => 'Other tenant news description',
        'tenant_id' => $otherTenant->id,
        'content_id' => $otherContent,
    ]);

    expect($this->tenantAdmin->can('view', $this->news))->toBeTrue()
        ->and($this->tenantAdmin->can('update', $this->news))->toBeTrue()
        ->and($this->tenantAdmin->can('view', $otherNews))->toBeFalse()
        ->and($this->tenantAdmin->can('update', $otherNews))->toBeFalse()
        ->and($this->superAdmin->can('view', $otherNews))->toBeTrue()
        ->and($this->superAdmin->can('update', $otherNews))->toBeTrue();
});

test('permission service works correctly', function (): void {
    $permissionService = app(PermissionService::class);

    expect($permissionService->isSuperAdmin($this->superAdmin))->toBeTrue()
        ->and($permissionService->isSuperAdmin($this->normalUser))->toBeFalse()
        ->and($permissionService->checkScope('news', 'read', 'padalinys', $this->tenantAdmin))->toBeTrue()
        ->and($permissionService->checkScope('news', 'read', 'all', $this->tenantAdmin))->toBeFalse();

    $superAdminTenants = $permissionService->getTenants('news.read.padalinys', $this->superAdmin);
    $tenantAdminTenants = $permissionService->getTenants('news.read.padalinys', $this->tenantAdmin);

    expect($superAdminTenants->count())->toBeGreaterThan(0)
        ->and($tenantAdminTenants->count())->toEqual(1)
        ->and($tenantAdminTenants->first()->id)->toEqual($this->tenant->id);
});
