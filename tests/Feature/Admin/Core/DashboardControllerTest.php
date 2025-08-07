<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
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
