<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = makeTenantUser();
    $this->admin = makeTenantUser('Communication Coordinator');
});

describe('auth: simple user', function () {
    beforeEach(function () {
        $response = asUser($this->user)->get(route('dashboard'));
        expect($response->status())->toBeSecureResponse();
    });

    test('can\'t index users', function () {
        $response = asUser($this->user)->get(route('users.index'));
        expect($response->status())->toRequireAuth();
    });

    test('can\'t access user create page', function () {
        $response = asUser($this->user)->get(route('users.create'));
        expect($response->status())->toRequireAuth();
    });

    test('can\'t store user', function () {
        $response = asUser($this->user)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@email.lt',
        ]);
        expect($response->status())->toRequireAuth();
    });

    test('can\' t access the user edit page', function () {
        $user = User::query()->first();
        $response = asUser($this->user)->get(route('users.edit', $user));
        expect($response->status())->toRequireAuth();
    });

    test('can\'t update user', function () {
        $user = User::query()->first();
        $response = asUser($this->user)->put(route('users.update', $user), [
            'name' => 'Test User Updated',
            'email' => 'test@mail.com',
        ]);
        expect($response->status())->toRequireAuth();
    });

    test('can\'t delete user', function () {
        $user = User::query()->first();
        $response = asUser($this->user)->delete(route('users.destroy', $user));
        expect($response->status())->toRequireAuth();
    });
});

it('can login')->todo();
