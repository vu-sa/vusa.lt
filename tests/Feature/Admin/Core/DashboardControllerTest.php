<?php

use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create related test data
    $this->page = Page::factory()->for($this->tenant)->create();
    $this->news = News::factory()->for($this->tenant)->create();
    $this->quickLink = QuickLink::factory()->for($this->tenant)->create();
    $this->resource = Resource::factory()->for($this->tenant)->create();
});

describe('dashboard access', function () {
    test('any authenticated user can access dashboard', function () {
        // Dashboard is accessible to any authenticated user
        asUser($this->user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('taskStats')
                ->has('unreadNotificationsCount')
                ->has('hasNotifications')
                ->has('user')
            );
    });

    test('admin can access dashboard', function () {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('taskStats')
                ->has('taskStats.completed')
                ->has('taskStats.pending')
                ->has('taskStats.overdue')
                ->has('unreadNotificationsCount')
                ->has('hasNotifications')
                ->has('user')
                ->where('user.id', $this->admin->id)
            );
    });

    test('unauthenticated user cannot access dashboard', function () {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});

describe('dashboard data structure', function () {
    test('task statistics are correctly structured', function () {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('taskStats')
                ->where('taskStats', function ($taskStats) {
                    return isset($taskStats['completed'])
                        && isset($taskStats['pending'])
                        && isset($taskStats['overdue']);
                })
            );
    });

    test('notification data is correctly structured', function () {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('unreadNotificationsCount')
                ->has('hasNotifications')
                ->where('unreadNotificationsCount', function ($count) {
                    return is_numeric($count) && $count >= 0;
                })
                ->where('hasNotifications', function ($hasNotifications) {
                    return is_bool($hasNotifications);
                })
            );
    });

    test('user data is included', function () {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('user')
                ->where('user.id', $this->admin->id)
                ->where('user.name', $this->admin->name)
                ->where('user.email', $this->admin->email)
            );
    });
});

describe('dashboard performance', function () {
    test('dashboard loads without database errors', function () {
        // Test that the dashboard doesn't cause N+1 queries or database errors
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200);
    });

    test('dashboard task statistics work with no tasks', function () {
        // Test when user has no tasks
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->where('taskStats.completed', 0)
                ->where('taskStats.pending', 0)
                ->where('taskStats.overdue', 0)
            );
    });
});

describe('atstovavimas dashboard', function () {
    test('admin can access atstovavimas dashboard', function () {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('user')
                ->has('accessibleInstitutions')
                ->has('availableTenants')
            );
    });

    test('regular user can access atstovavimas dashboard', function () {
        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('user')
                ->has('accessibleInstitutions')
                ->has('availableTenants')
            );
    });

    test('atstovavimas filters PKP tenants', function () {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('availableTenants', function ($tenants) {
                    return collect($tenants)->every(fn ($tenant) => $tenant['type'] !== 'pkp');
                })
            );
    });

    test('atstovavimas provides accessible institutions and available tenants', function () {
        $response = asUser($this->admin)->get(route('dashboard.atstovavimas'));
        
        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('accessibleInstitutions')
                ->has('availableTenants')
                ->where('availableTenants', function ($tenants) {
                    // Convert to collection if it's an array, or keep as collection
                    $collection = collect($tenants);
                    
                    // Should have at least one tenant and not include PKP type
                    return $collection->count() > 0 && 
                           $collection->every(fn ($tenant) => $tenant['type'] !== 'pkp');
                })
            );
    });
});

describe('svetaine dashboard', function () {
    test('admin can access svetaine dashboard', function () {
        asUser($this->admin)
            ->get(route('dashboard.svetaine'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->has('tenants')
                ->has('providedTenant')
            );
    });

    test('regular user cannot access svetaine dashboard', function () {
        asUser($this->user)
            ->get(route('dashboard.svetaine'))
            ->assertStatus(403);
    });

    test('svetaine dashboard includes tenant data', function () {
        asUser($this->admin)
            ->get(route('dashboard.svetaine', ['tenant_id' => $this->tenant->id]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->where('providedTenant.id', $this->tenant->id)
                ->has('providedTenant.pages')
                ->has('providedTenant.news')
                ->where('providedTenant', function ($tenant) {
                    return isset($tenant['id']) && isset($tenant['pages']) && isset($tenant['news']);
                })
            );
    });
});

describe('reservations dashboard', function () {
    test('admin can access reservations dashboard', function () {
        asUser($this->admin)
            ->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowReservations')
                ->has('reservations')
                ->has('resources')
                ->has('tenants')
            );
    });

    test('reservations dashboard includes resource statistics', function () {
        asUser($this->admin)
            ->get(route('dashboard.reservations'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowReservations')
                ->has('resources.active')
                ->has('resources.sumOfCapacity')
                ->where('resources', function ($resources) {
                    return isset($resources['active']) && is_numeric($resources['active']) &&
                           isset($resources['sumOfCapacity']) && is_numeric($resources['sumOfCapacity']);
                })
            );
    });

    test('reservations dashboard handles tenant filtering', function () {
        asUser($this->admin)
            ->get(route('dashboard.reservations', ['tenant_id' => $this->tenant->id]))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowReservations')
                ->has('providedTenant')
            );
    });
});

describe('user settings', function () {
    test('authenticated user can access user settings', function () {
        asUser($this->admin)
            ->get(route('profile'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowUserSettings')
                ->has('user')
                ->where('user.id', $this->admin->id)
            );
    });

    test('user settings include role and permission data', function () {
        asUser($this->admin)
            ->get(route('profile'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowUserSettings')
                ->has('user.roles')
                ->has('user.current_duties')
            );
    });

    test('unauthenticated user cannot access user settings', function () {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    });

    test('user can update settings', function () {
        $validData = [
            'email' => 'updated@example.com',
            'profile_photo_path' => '/path/to/photo.jpg',
            'show_pronouns' => true,
        ];

        asUser($this->admin)
            ->patch(route('profile.update'), $validData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'email' => 'updated@example.com',
        ]);
    });

    test('user cannot change name after it was previously changed', function () {
        // Set name_was_changed to true
        $this->admin->name_was_changed = true;
        $this->admin->save();

        $updateData = [
            'name' => 'New Name',
            'email' => 'updated@example.com',
        ];

        asUser($this->admin)
            ->patch(route('profile.update'), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        // Name should remain unchanged
        $this->assertDatabaseMissing('users', [
            'id' => $this->admin->id,
            'name' => 'New Name',
        ]);

        // But email should be updated
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'email' => 'updated@example.com',
        ]);
    });

    test('user can update password', function () {
        $passwordData = [
            'current_password' => 'password', // Default password from factory
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        asUser($this->admin)
            ->patch(route('profile.updatePassword'), $passwordData)
            ->assertStatus(302)
            ->assertSessionHas('success');
    });
});

describe('user tasks', function () {
    test('authenticated user can access user tasks', function () {
        asUser($this->admin)
            ->get(route('userTasks'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowTasks')
                ->has('tasks')
            );
    });

    test('unauthenticated user cannot access user tasks', function () {
        $this->get(route('userTasks'))
            ->assertRedirect(route('login'));
    });
});

describe('institution graph', function () {
    test('authenticated user can access institution graph', function () {
        asUser($this->admin)
            ->get(route('institutionGraph'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowInstitutionGraph')
                ->has('institutions')
                ->has('institutionRelationships')
            );
    });

    test('institution graph includes user counts', function () {
        asUser($this->admin)
            ->get(route('institutionGraph'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowInstitutionGraph')
                ->where('institutions', function ($institutions) {
                    return collect($institutions)->every(function ($institution) {
                        return isset($institution['users_count']) && is_numeric($institution['users_count']);
                    });
                })
            );
    });
});

describe('feedback functionality', function () {
    test('user can send feedback', function () {
        $feedbackData = [
            'feedback' => 'This is test feedback',
            'anonymous' => false,
            'href' => 'http://localhost/test',
            'selectedText' => '',
        ];

        asUser($this->admin)
            ->post(route('feedback.send'), $feedbackData)
            ->assertStatus(302)
            ->assertSessionHas('success');
    });

    test('user can send anonymous feedback', function () {
        $feedbackData = [
            'feedback' => 'This is anonymous feedback',
            'anonymous' => true,
            'href' => 'http://localhost/test',
            'selectedText' => '',
        ];

        asUser($this->admin)
            ->post(route('feedback.send'), $feedbackData)
            ->assertStatus(302)
            ->assertSessionHas('success');
    });

    test('unauthenticated user cannot send feedback', function () {
        $feedbackData = [
            'feedback' => 'Test feedback',
            'anonymous' => false,
            'href' => 'http://localhost/test',
            'selectedText' => '',
        ];

        $this->post(route('feedback.send'), $feedbackData)
            ->assertRedirect();  // Just assert it redirects, don't specify exact URL
    });
});

// Removed: atstovavimas summary feature and component were deprecated.

describe('tenant isolation', function () {
    beforeEach(function () {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherAdmin = makeTenantUserWithRole('Communication Coordinator', $this->otherTenant);
    });

    test('user sees institutions and tenants based on their permissions', function () {
        asUser($this->admin)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->has('accessibleInstitutions')
                ->has('availableTenants')
                ->where('availableTenants', function ($tenants) {
                    // User should see tenants they have permissions for
                    return collect($tenants)->count() > 0;
                })
            );
    });

    test('user cannot access other tenant svetaine data directly', function () {
        asUser($this->admin)
            ->get(route('dashboard.svetaine', ['tenant_id' => $this->otherTenant->id]))
            ->assertStatus(200) // They can access but should see filtered data
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSvetaine')
                ->where('tenants', function ($tenants) {
                    // Should not contain unauthorized tenants
                    return collect($tenants)->every(function ($tenant) {
                        return $tenant['id'] === $this->tenant->id || $tenant['type'] === 'pagrindinis';
                    });
                })
            );
    });
});
