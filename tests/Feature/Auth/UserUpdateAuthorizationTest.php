<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->admin = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
    $this->targetUser = makeUser($this->tenant);
});

describe('user update authorization with singleton authorizer', function () {
    test('coordinator can view edit page for user in same tenant', function () {
        asUser($this->admin)
            ->get(route('users.edit', $this->targetUser))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/EditUser')
            );
    });

    test('coordinator can update user in same tenant', function () {
        $response = asUser($this->admin)
            ->patch(route('users.update', $this->targetUser), [
                'name' => 'Updated Name',
                'email' => $this->targetUser->email,
                'current_duties' => [],
            ]);

        $response->assertRedirect();
        expect($this->targetUser->fresh()->name)->toBe('Updated Name');
    });

    test('edit then update in same session succeeds with cached authorizer', function () {
        $admin = asUser($this->admin);

        // First request — edit page (policy check caches permissions)
        $admin->get(route('users.edit', $this->targetUser))
            ->assertStatus(200);

        // Second request — update (should succeed using cached permissions)
        $response = $admin->patch(route('users.update', $this->targetUser), [
            'name' => 'Updated Via Sequential Requests',
            'email' => $this->targetUser->email,
            'current_duties' => [],
        ]);

        $response->assertRedirect();
        expect($this->targetUser->fresh()->name)->toBe('Updated Via Sequential Requests');
    });

    test('coordinator cannot update user in different tenant', function () {
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherUser = makeUser($otherTenant);

        asUser($this->admin)
            ->patch(route('users.update', $otherUser), [
                'name' => 'Should Not Work',
                'email' => $otherUser->email,
                'current_duties' => [],
            ])
            ->assertStatus(403);
    });

    test('normal user cannot update any user', function () {
        $normalUser = makeUser($this->tenant);

        asUser($normalUser)
            ->patch(route('users.update', $this->targetUser), [
                'name' => 'Should Not Work',
                'email' => $this->targetUser->email,
                'current_duties' => [],
            ])
            ->assertStatus(403);
    });

    test('multiple edit-update cycles succeed with same session', function () {
        $admin = asUser($this->admin);
        $secondUser = makeUser($this->tenant);

        // First cycle — edit + update user 1
        $admin->get(route('users.edit', $this->targetUser))->assertStatus(200);
        $admin->patch(route('users.update', $this->targetUser), [
            'name' => 'First Update',
            'email' => $this->targetUser->email,
            'current_duties' => [],
        ])->assertRedirect();

        // Second cycle — edit + update user 2
        $admin->get(route('users.edit', $secondUser))->assertStatus(200);
        $admin->patch(route('users.update', $secondUser), [
            'name' => 'Second Update',
            'email' => $secondUser->email,
            'current_duties' => [],
        ])->assertRedirect();

        expect($this->targetUser->fresh()->name)->toBe('First Update');
        expect($secondUser->fresh()->name)->toBe('Second Update');
    });
});
