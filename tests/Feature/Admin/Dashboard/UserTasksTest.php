<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

describe('user tasks', function (): void {
    test('authenticated user can access user tasks', function (): void {
        asUser($this->admin)
            ->get(route('userTasks'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowTasks')
                ->has('tasks')
            );
    });

    test('unauthenticated user cannot access user tasks', function (): void {
        $this->get(route('userTasks'))
            ->assertRedirect(route('login'));
    });
});
