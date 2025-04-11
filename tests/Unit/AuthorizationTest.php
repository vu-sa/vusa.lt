<?php

use App\Facades\Permission;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createContent(): string
{
    $content = \App\Models\Content::create();
    $content->parts()->createMany([
        ['type' => 'text', 'json_content' => json_encode(['text' => 'Test content'])],
        ['type' => 'image', 'json_content' => json_encode(['url' => 'test.jpg'])],
    ]);
    $content->save();

    return $content->id;
}

beforeEach(function () {
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

    $this->tenantAdminRole = Role::create(['name' => 'Tenant Admin', 'guard_name' => 'web']);
    $this->contentEditorRole = Role::create(['name' => 'Content Editor', 'guard_name' => 'web']);

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

    $this->superAdmin = User::factory()->create();
    $this->normalUser = User::factory()->create();
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

test('super admin has all permissions', function () {
    expect(Permission::check('news.read.all', $this->superAdmin))->toBeTrue();
    expect(Permission::check('news.create.all', $this->superAdmin))->toBeTrue();
    expect(Permission::check('news.update.all', $this->superAdmin))->toBeTrue();
    expect(Permission::check('news.delete.all', $this->superAdmin))->toBeTrue();
    expect(Permission::check('unknown.permission.scope', $this->superAdmin))->toBeTrue();
});

test('tenant admin has tenant scoped permissions', function () {
    expect(Permission::check('news.read.padalinys', $this->tenantAdmin))->toBeTrue();
    expect(Permission::check('news.create.padalinys', $this->tenantAdmin))->toBeTrue();
    expect(Permission::check('news.update.padalinys', $this->tenantAdmin))->toBeTrue();
    expect(Permission::check('news.delete.padalinys', $this->tenantAdmin))->toBeTrue();
    expect(Permission::check('news.read.all', $this->tenantAdmin))->toBeFalse();
});

test('normal user has no permissions', function () {
    expect(Permission::check('news.read.padalinys', $this->normalUser))->toBeFalse();
    expect(Permission::check('news.create.padalinys', $this->normalUser))->toBeFalse();
    expect(Permission::check('news.update.padalinys', $this->normalUser))->toBeFalse();
    expect(Permission::check('news.delete.padalinys', $this->normalUser))->toBeFalse();
});

test('permission cache is cleared when roles change')->todo();

test('permission cache is cleared when duties change')->todo();

// TODO: Need to have better checks
test('policy checks work correctly', function () {
    expect($this->superAdmin->can('view', $this->news))->toBeTrue();
    expect($this->superAdmin->can('update', $this->news))->toBeTrue();
    expect($this->superAdmin->can('delete', $this->news))->toBeTrue();

    expect($this->tenantAdmin->can('view', $this->news))->toBeTrue();
    expect($this->tenantAdmin->can('update', $this->news))->toBeTrue();
    expect($this->tenantAdmin->can('delete', $this->news))->toBeTrue();

    expect($this->normalUser->can('view', $this->news))->toBeFalse();
    expect($this->normalUser->can('update', $this->news))->toBeFalse();
    expect($this->normalUser->can('delete', $this->news))->toBeFalse();

    $this->normalUser->duties()->attach($this->duty->id, ['start_date' => now()->subDay()]);
    $this->normalUser->assignRole($this->contentEditorRole);

    expect($this->normalUser->can('view', $this->news))->toBeTrue();
    expect($this->normalUser->can('update', $this->news))->toBeTrue();
    expect($this->normalUser->can('delete', $this->news))->toBeFalse();
})->todo();

test('tenant scoped permissions only apply to correct tenant', function () {
    $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
    $otherContent = createContent();
    $otherNews = News::create([
        'title' => 'Other Tenant News',
        'short' => 'Other tenant news description',
        'tenant_id' => $otherTenant->id,
        'content_id' => $otherContent,
    ]);

    expect($this->tenantAdmin->can('view', $this->news))->toBeTrue();
    expect($this->tenantAdmin->can('update', $this->news))->toBeTrue();

    expect($this->tenantAdmin->can('view', $otherNews))->toBeFalse();
    expect($this->tenantAdmin->can('update', $otherNews))->toBeFalse();

    expect($this->superAdmin->can('view', $otherNews))->toBeTrue();
    expect($this->superAdmin->can('update', $otherNews))->toBeTrue();
});

test('permission service works correctly', function () {
    $permissionService = app(PermissionService::class);

    expect($permissionService->isSuperAdmin($this->superAdmin))->toBeTrue();
    expect($permissionService->isSuperAdmin($this->normalUser))->toBeFalse();

    expect($permissionService->checkScope('news', 'read', 'padalinys', $this->tenantAdmin))->toBeTrue();
    expect($permissionService->checkScope('news', 'read', 'all', $this->tenantAdmin))->toBeFalse();

    $superAdminTenants = $permissionService->getTenants($this->superAdmin);
    $tenantAdminTenants = $permissionService->getTenants($this->tenantAdmin);

    expect($superAdminTenants->count())->toBeGreaterThan(0);
    expect($tenantAdminTenants->count())->toEqual(1);
    expect($tenantAdminTenants->first()->id)->toEqual($this->tenant->id);
});
