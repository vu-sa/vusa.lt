<?php

use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

describe('unauthorized access', function () {
    beforeEach(function () {
        $this->user = makeUser($this->tenant);
        $response = asUser($this->user)->get(route('dashboard'));
        expect($response->status())->toBeSecureResponse();
    });

    test('cannot index users', function () {
        $response = asUser($this->user)->get(route('users.index'));
        expect($response->status())->toRequireAuth();
    });

    test('cannot access user create page', function () {
        $response = asUser($this->user)->get(route('users.create'));
        expect($response->status())->toRequireAuth();
    });

    test('cannot store user', function () {
        $response = asUser($this->user)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        expect($response->status())->toRequireAuth();
    });

    test('cannot access user edit page', function () {
        $user = User::factory()->create();
        $response = asUser($this->user)->get(route('users.edit', $user));
        expect($response->status())->toRequireAuth();
    });

    test('cannot update user', function () {
        $user = User::factory()->create();
        $response = asUser($this->user)->put(route('users.update', $user), [
            'name' => 'Test User Updated',
            'email' => 'updated@example.com',
        ]);
        expect($response->status())->toRequireAuth();
    });

    test('cannot delete user', function () {
        $user = User::factory()->create();
        $response = asUser($this->user)->delete(route('users.destroy', $user));
        expect($response->status())->toRequireAuth();
    });
});

describe('authorized access', function () {
    beforeEach(function () {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('can index users', function () {
        User::factory()->count(3)->create();

        $response = asUser($this->admin)->get(route('users.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexUser')
                ->has('users')
            );
    });

    test('can access user create page', function () {
        $response = asUser($this->admin)->get(route('users.create'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/CreateUser')
            );
    });

    test('can store user', function () {
        $duty = \App\Models\Duty::factory()->create();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+370 123 4567',
            'current_duties' => [$duty->id],
        ];

        $response = asUser($this->admin)->post(route('users.store'), $userData);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    });

    test('can access user edit page', function () {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->get(route('users.edit', $user));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/EditUser')
                ->has('user')
                ->where('user.id', $user->id)
            );
    });

    test('can update user', function () {
        $user = makeUser($this->tenant);

        $updateData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'phone' => '+370 987 6543',
        ];

        $response = asUser($this->admin)->put(route('users.update', $user), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ]);
    });

    test('can delete user', function () {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->delete(route('users.destroy', $user));

        $response->assertRedirect();

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    });
});

describe('validation', function () {
    beforeEach(function () {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('requires name for store', function () {
        $response = asUser($this->admin)->post(route('users.store'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name');
    });

    test('requires email for store', function () {
        $response = asUser($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires valid email format for store', function () {
        $response = asUser($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires unique email for store', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = asUser($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires name for update', function () {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->put(route('users.update', $user), [
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name');
    });

    test('requires unique email for update', function () {
        $existingUser = makeUser($this->tenant);
        $existingUser->update(['email' => 'existing@example.com']);
        $user = makeUser($this->tenant);
        $user->update(['email' => 'user@example.com']);

        $response = asUser($this->admin)->put(route('users.update', $user), [
            'name' => 'Updated User',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });
});

describe('relationships', function () {
    beforeEach(function () {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('user has proper model structure', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+370 123 4567',
        ]);

        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->phone)->toBe('+370 123 4567');
    });

    test('can retrieve user by email', function () {
        $user = User::factory()->create(['email' => 'unique@example.com']);

        $foundUser = User::where('email', 'unique@example.com')->first();

        expect($foundUser)->not->toBeNull();
        expect($foundUser->id)->toBe($user->id);
    });

    test('user can have duties', function () {
        $user = User::factory()->create();

        // Check if user can have duties (relationship exists)
        expect($user->duties())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});
