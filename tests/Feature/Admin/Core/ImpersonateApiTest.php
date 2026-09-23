<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.env' => 'local']);
});

test('a super admin can impersonate another super admin and return', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(config('permission.super_admin_role_name'));
    $target = User::factory()->create();
    $target->assignRole(config('permission.super_admin_role_name'));

    asUser($actor)->postJson(route('api.v1.admin.impersonate.start'), [
        'user_id' => $target->id,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.impersonating.id', $target->id)
        ->assertSessionHas('impersonator_id', $actor->id);

    $this->assertAuthenticatedAs($target);

    $this->postJson(route('api.v1.admin.impersonate.stop'))
        ->assertOk()
        ->assertSessionMissing('impersonator_id');

    $this->assertAuthenticatedAs($actor);
});

test('a regular member cannot start impersonation', function (): void {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $target->assignRole(config('permission.super_admin_role_name'));

    asUser($actor)->postJson(route('api.v1.admin.impersonate.start'), [
        'user_id' => $target->id,
    ])->assertForbidden();

    $this->assertAuthenticatedAs($actor);
});

test('an impersonated super admin cannot replace the original actor', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(config('permission.super_admin_role_name'));
    $target = User::factory()->create();
    $target->assignRole(config('permission.super_admin_role_name'));
    $nextTarget = User::factory()->create();

    asUser($actor)->postJson(route('api.v1.admin.impersonate.start'), [
        'user_id' => $target->id,
    ])->assertOk();

    $this->postJson(route('api.v1.admin.impersonate.start'), [
        'user_id' => $nextTarget->id,
    ])->assertStatus(409)->assertSessionHas('impersonator_id', $actor->id);

    $this->assertAuthenticatedAs($target);
});
