<?php

use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->user = makeUser($this->tenant);

    $this->admin = makeUserManager($this->tenant);
});

function makeUserManager($tenant): User
{
    $user = makeUser($tenant);

    $user->duties()->first()->assignRole('Communication Coordinator');

    return $user;
}

describe('auth: simple user', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index users', function () {
        asUser($this->user)->get(route('users.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t access user create page', function () {
        asUser($this->user)->get(route('users.create'))->assertStatus(302);
    });

    test('can\'t store user', function () {
        asUser($this->user)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@email.lt',
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\' t access the user edit page', function () {
        $user = User::query()->first();

        asUser($this->user)->get(route('users.edit', $user))->assertStatus(302);
    });

    test('can\'t update user', function () {
        $user = User::query()->first();

        asUser($this->user)->put(route('calendar.update', $user), [
            'name' => 'Test User Updated',
            'email' => 'test@mail.com',
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t delete user', function () {
        $user = User::query()->first();

        asUser($this->user)->delete(route('users.destroy', $user))->assertStatus(302);
    });
});
