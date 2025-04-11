<?php

namespace Tests\Unit;

use App\Enums\CRUDEnum;
use App\Facades\Permission;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $normalUser;
    protected User $tenantAdmin;
    protected Tenant $tenant;
    protected Institution $institution;
    protected Duty $duty;
    protected Role $tenantAdminRole;
    protected Role $contentEditorRole;
    protected News $news;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::factory()->create([
            'type' => 'padalinys',
            'alias' => 'test-tenant'
        ]);

        // Create an institution for the tenant
        $this->institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Create a duty connected to the institution
        $this->duty = Duty::factory()->create([
            'institution_id' => $this->institution->id
        ]);

        // Create roles
        $this->tenantAdminRole = Role::create(['name' => 'Tenant Admin', 'guard_name' => 'web']);
        $this->contentEditorRole = Role::create(['name' => 'Content Editor', 'guard_name' => 'web']);

        // Assign permissions to roles
        $this->tenantAdminRole->givePermissionTo([
            'news.read.padalinys',
            'news.create.padalinys',
            'news.update.padalinys',
            'news.delete.padalinys'
        ]);

        $this->contentEditorRole->givePermissionTo([
            'news.read.padalinys',
            'news.create.padalinys',
            'news.update.own',
        ]);

        // Create users
        $this->superAdmin = User::factory()->create();
        $this->normalUser = User::factory()->create();
        $this->tenantAdmin = User::factory()->create();

        // Assign roles
        $this->superAdmin->assignRole(config('permission.super_admin_role_name'));

        // Assign duty with roles
        $this->duty->assignRole($this->tenantAdminRole);
        $this->tenantAdmin->duties()->attach($this->duty->id, ['start_date' => now()->subDay()]);

        // Create test content with required content_id
        $this->news = News::create([
            'title' => 'Test News',
            'short' => 'Test short description',
            'tenant_id' => $this->tenant->id,
            'content_id' => $this->createContent(),  // Add content_id
        ]);
    }

    /**
     * Create a Content model and return its ID for testing
     */
    protected function createContent(): string
    {
        $content = \App\Models\Content::create([
            'json_content' => json_encode(['type' => 'doc', 'content' => []]),
        ]);

        return $content->id;
    }

    /** @test */
    public function super_admin_has_all_permissions()
    {
        $this->assertTrue(Permission::check('news.read.all', $this->superAdmin));
        $this->assertTrue(Permission::check('news.create.all', $this->superAdmin));
        $this->assertTrue(Permission::check('news.update.all', $this->superAdmin));
        $this->assertTrue(Permission::check('news.delete.all', $this->superAdmin));
        
        // Super admin should have access to any permission
        $this->assertTrue(Permission::check('unknown.permission.scope', $this->superAdmin));
    }
    
    /** @test */
    public function tenant_admin_has_tenant_scoped_permissions()
    {
        $this->assertTrue(Permission::check('news.read.padalinys', $this->tenantAdmin));
        $this->assertTrue(Permission::check('news.create.padalinys', $this->tenantAdmin));
        $this->assertTrue(Permission::check('news.update.padalinys', $this->tenantAdmin));
        $this->assertTrue(Permission::check('news.delete.padalinys', $this->tenantAdmin));
        
        // But doesn't have global scope
        $this->assertFalse(Permission::check('news.read.all', $this->tenantAdmin));
    }
    
    /** @test */
    public function normal_user_has_no_permissions()
    {
        $this->assertFalse(Permission::check('news.read.padalinys', $this->normalUser));
        $this->assertFalse(Permission::check('news.create.padalinys', $this->normalUser));
        $this->assertFalse(Permission::check('news.update.padalinys', $this->normalUser));
        $this->assertFalse(Permission::check('news.delete.padalinys', $this->normalUser));
    }
    
    /** @test */
    public function permission_cache_is_cleared_when_roles_change()
    {
        // Initially user doesn't have permission
        $this->assertFalse(Permission::check('news.read.padalinys', $this->normalUser));
        
        // Assign role
        $this->normalUser->assignRole($this->contentEditorRole);
        
        // Permission should be updated immediately
        $this->assertTrue(Permission::check('news.read.padalinys', $this->normalUser));
        
        // Remove role
        $this->normalUser->removeRole($this->contentEditorRole);
        
        // Permission should be revoked
        $this->assertFalse(Permission::check('news.read.padalinys', $this->normalUser));
    }
    
    /** @test */
    public function permission_cache_is_cleared_when_duties_change()
    {
        // Initially user doesn't have permission
        $this->assertFalse(Permission::check('news.read.padalinys', $this->normalUser));
        
        // Assign duty
        $this->normalUser->duties()->attach($this->duty->id, ['start_date' => now()->subDay()]);
        
        // Permission should be updated immediately
        $this->assertTrue(Permission::check('news.read.padalinys', $this->normalUser));
        
        // Remove duty
        $this->normalUser->duties()->detach($this->duty->id);
        
        // Permission should be revoked
        $this->assertFalse(Permission::check('news.read.padalinys', $this->normalUser));
    }
    
    /** @test */
    public function policy_checks_work_correctly()
    {
        // Super admin can do anything
        $this->assertTrue($this->superAdmin->can('view', $this->news));
        $this->assertTrue($this->superAdmin->can('update', $this->news));
        $this->assertTrue($this->superAdmin->can('delete', $this->news));
        
        // Tenant admin can do tenant-scoped operations
        $this->assertTrue($this->tenantAdmin->can('view', $this->news));
        $this->assertTrue($this->tenantAdmin->can('update', $this->news));
        $this->assertTrue($this->tenantAdmin->can('delete', $this->news));
        
        // Normal user can't do anything
        $this->assertFalse($this->normalUser->can('view', $this->news));
        $this->assertFalse($this->normalUser->can('update', $this->news));
        $this->assertFalse($this->normalUser->can('delete', $this->news));
        
        // Give limited permissions via content editor role
        $this->normalUser->assignRole($this->contentEditorRole);
        
        // Now user can view and update but not delete
        $this->assertTrue($this->normalUser->can('view', $this->news));
        $this->assertTrue($this->normalUser->can('update', $this->news)); // Own scope will allow this
        $this->assertFalse($this->normalUser->can('delete', $this->news));
    }
    
    /** @test */
    public function tenant_scoped_permissions_only_apply_to_correct_tenant()
    {
        // Create another tenant and news
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherNews = News::create([
            'title' => 'Other Tenant News',
            'short' => 'Other tenant news description',
            'tenant_id' => $otherTenant->id
        ]);
        
        // Tenant admin can manage own tenant's news
        $this->assertTrue($this->tenantAdmin->can('view', $this->news));
        $this->assertTrue($this->tenantAdmin->can('update', $this->news));
        
        // But not another tenant's news
        $this->assertFalse($this->tenantAdmin->can('view', $otherNews));
        $this->assertFalse($this->tenantAdmin->can('update', $otherNews));
        
        // Super admin can manage all tenants' news
        $this->assertTrue($this->superAdmin->can('view', $otherNews));
        $this->assertTrue($this->superAdmin->can('update', $otherNews));
    }
    
    /** @test */
    public function permission_service_works_correctly()
    {
        $permissionService = app(PermissionService::class);
        
        // Check structure and main operations
        $this->assertTrue($permissionService->isSuperAdmin($this->superAdmin));
        $this->assertFalse($permissionService->isSuperAdmin($this->normalUser));
        
        // Check scope-based permission checking
        $this->assertTrue($permissionService->checkScope('news', 'read', 'padalinys', $this->tenantAdmin));
        $this->assertFalse($permissionService->checkScope('news', 'read', 'all', $this->tenantAdmin));
        
        // Test tenant retrieval
        $superAdminTenants = $permissionService->getTenants($this->superAdmin);
        $tenantAdminTenants = $permissionService->getTenants($this->tenantAdmin);
        
        $this->assertGreaterThan(0, $superAdminTenants->count());
        $this->assertEquals(1, $tenantAdminTenants->count());
        $this->assertEquals($this->tenant->id, $tenantAdminTenants->first()->id);
    }
}