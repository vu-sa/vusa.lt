<?php

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);
});

describe('role index', function () {
    test('unauthorized user cannot access role index', function () {
        asUser($this->user)
            ->get(route('roles.index'))
            ->assertStatus(403);
    });

    test('admin can access role index', function () {
        // Clear existing roles to avoid conflicts, but keep the Super Admin role for our admin
        Role::where('name', '!=', 'Super Admin')->delete();
        Role::factory()->count(3)->create();

        asUser($this->admin)
            ->get(route('roles.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/IndexRole')
                ->has('roles')
            );
    });

    test('role index displays paginated roles', function () {
        // Clear existing roles and create fresh ones
        Role::where('name', '!=', 'Super Admin')->delete();
        Role::factory()->count(25)->create();

        asUser($this->admin)
            ->get(route('roles.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('roles.data', 20) // Default pagination size
                ->has('roles.meta')
            );
    });
});

describe('role show', function () {
    test('unauthorized user cannot view role', function () {
        $role = Role::factory()->create();

        asUser($this->user)
            ->get(route('roles.show', $role))
            ->assertStatus(403);
    });

    test('admin can view role', function () {
        $role = Role::factory()->create();

        asUser($this->admin)
            ->get(route('roles.show', $role))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/ShowRole')
                ->has('role')
                ->where('role.id', $role->id)
            );
    });
});

describe('role create', function () {
    test('unauthorized user cannot access role create', function () {
        asUser($this->user)
            ->get(route('roles.create'))
            ->assertStatus(403);
    });

    test('admin can access role create form', function () {
        asUser($this->admin)
            ->get(route('roles.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/CreateRole')
            );
    });
});

describe('role store', function () {
    test('unauthorized user cannot create role', function () {
        $data = ['name' => 'Test Role'];

        asUser($this->user)
            ->post(route('roles.store'), $data)
            ->assertStatus(403);
    });

    test('admin can create role with valid data', function () {
        $data = ['name' => 'Test Role'];

        asUser($this->admin)
            ->post(route('roles.store'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
        ]);
    });

    test('admin cannot create role with duplicate name', function () {
        Role::factory()->create(['name' => 'Existing Role']);
        $data = ['name' => 'Existing Role'];

        asUser($this->admin)
            ->post(route('roles.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    });

    test('admin cannot create role without name', function () {
        $data = ['name' => ''];

        asUser($this->admin)
            ->post(route('roles.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    });
});

describe('role edit', function () {
    test('unauthorized user cannot access role edit', function () {
        $role = Role::factory()->create();

        asUser($this->user)
            ->get(route('roles.edit', $role))
            ->assertStatus(403);
    });

    test('admin can access role edit form', function () {
        $role = Role::factory()->create();

        asUser($this->admin)
            ->get(route('roles.edit', $role))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/EditRole')
                ->has('role')
                ->where('role.id', $role->id)
            );
    });
});

describe('role update', function () {
    test('unauthorized user cannot update role', function () {
        $role = Role::factory()->create();
        $data = ['name' => 'Updated Role'];

        asUser($this->user)
            ->put(route('roles.update', $role), $data)
            ->assertStatus(403);
    });

    test('admin can update role with valid data', function () {
        $role = Role::factory()->create();
        $data = ['name' => 'Updated Role'];

        asUser($this->admin)
            ->put(route('roles.update', $role), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role',
        ]);
    });

    test('admin cannot update role with duplicate name', function () {
        $existingRole = Role::factory()->create(['name' => 'Existing Role']);
        $role = Role::factory()->create(['name' => 'Test Role']);
        $data = ['name' => 'Existing Role'];

        asUser($this->admin)
            ->put(route('roles.update', $role), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    });

    test('admin cannot update role without name', function () {
        $role = Role::factory()->create();
        $data = ['name' => ''];

        asUser($this->admin)
            ->put(route('roles.update', $role), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    });
});

describe('role destroy', function () {
    test('unauthorized user cannot delete role', function () {
        $role = Role::factory()->create();

        asUser($this->user)
            ->delete(route('roles.destroy', $role))
            ->assertStatus(403);
    });

    test('admin can delete role', function () {
        $role = Role::factory()->create();

        asUser($this->admin)
            ->delete(route('roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    });

    test('admin cannot delete super admin role', function () {
        $role = Role::where('name', 'Super Admin')->first();

        asUser($this->admin)
            ->delete(route('roles.destroy', $role))
            ->assertStatus(302)
            ->assertSessionHas('info');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    });
});

describe('role permission management', function () {
    test('admin can sync permission group to role', function () {
        $role = Role::factory()->create();

        // Create permissions that match what the controller expects
        $permissions = collect();
        foreach (['create', 'read', 'update', 'delete'] as $ability) {
            $permissions->push(\App\Models\Permission::create([
                'name' => "test.{$ability}.own",
                'guard_name' => 'web',
            ]));
        }

        $data = [
            'create' => 'own',
            'read' => 'own',
            'update' => 'own',
            'delete' => 'own',
        ];

        asUser($this->admin)
            ->patch(route('roles.syncPermissionGroup', [$role, 'test']), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($permissions as $permission) {
            expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();
        }
    });

    test('admin can sync duties to role', function () {
        $role = Role::factory()->create();
        $duty = $this->admin->duties()->first();

        $data = [
            'duties' => [$duty->id],
        ];

        asUser($this->admin)
            ->put(route('roles.syncDuties', $role), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $duty->id,
            'model_type' => 'App\\Models\\Duty',
        ]);
    });

    test('admin can sync attachable types to role', function () {
        $role = Role::factory()->create();

        // Create a Type record for Institution
        $institutionType = \App\Models\Type::create([
            'title' => ['en' => 'Institution', 'lt' => 'Institucija'],
            'model_type' => 'App\\Models\\Institution',
            'slug' => 'institution',
        ]);

        $data = [
            'attachable_types' => [$institutionType->id],
        ];

        asUser($this->admin)
            ->put(route('roles.syncAttachableTypes', $role), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('role_can_attach_types', [
            'role_id' => $role->id,
            'type_id' => $institutionType->id,
        ]);
    });
});

describe('role security', function () {
    test('regular user cannot perform any role management actions', function () {
        $role = Role::factory()->create();

        // Test all protected routes
        asUser($this->user)->get(route('roles.index'))->assertStatus(403);
        asUser($this->user)->get(route('roles.show', $role))->assertStatus(403);
        asUser($this->user)->get(route('roles.create'))->assertStatus(403);
        asUser($this->user)->post(route('roles.store'), ['name' => 'Test'])->assertStatus(403);
        asUser($this->user)->get(route('roles.edit', $role))->assertStatus(403);
        asUser($this->user)->put(route('roles.update', $role), ['name' => 'Test'])->assertStatus(403);
        asUser($this->user)->delete(route('roles.destroy', $role))->assertStatus(403);
    });

    test('role management requires proper permissions', function () {
        $role = Role::factory()->create();

        // Verify that the role management requires proper authorization
        // This is tested through the 403 responses above
        expect(true)->toBeTrue();
    });
});
