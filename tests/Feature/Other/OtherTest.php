<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('User Settings', function () {
    test('user can set own settings')->todo();
});

describe('User Password Management', function () {
    test('user can update password with valid current password', function () {
        $user = makeTenantUser('Communication Coordinator');
        $user->password = bcrypt('old-password');
        $user->save();

        $response = asUser($user)->patch(route('profile.updatePassword'), [
            'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Slaptažodis sėkmingai pakeistas.');

        // Verify password was actually changed
        $user->refresh();
        expect(\Illuminate\Support\Facades\Hash::check('new-secure-password', $user->password))->toBeTrue();
    });

    test('user cannot update password with incorrect current password', function () {
        $user = makeTenantUser('Communication Coordinator');
        $user->password = bcrypt('old-password');
        $user->save();

        $response = asUser($user)->patch(route('profile.updatePassword'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertSessionHasErrors('current_password');
    });

    test('user cannot update password without confirmation', function () {
        $user = makeTenantUser('Communication Coordinator');
        $user->password = bcrypt('old-password');
        $user->save();

        $response = asUser($user)->patch(route('profile.updatePassword'), [
            'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
    });
});

describe('User Password Attribute', function () {
    test('has_password attribute returns true when password exists', function () {
        $user = User::factory()->create([
            'password' => bcrypt('test-password'),
        ]);

        expect($user->has_password)->toBeTrue();
    });

    test('has_password attribute returns false when password is null', function () {
        $user = User::factory()->create([
            'password' => null,
        ]);

        expect($user->has_password)->toBeFalse();
    });

    test('has_password attribute is included when explicitly appended', function () {
        $user = User::factory()->create([
            'password' => bcrypt('test-password'),
        ]);

        $array = $user->append('has_password')->toArray();

        expect($array)->toHaveKey('has_password');
        expect($array['has_password'])->toBeTrue();
    });
});
