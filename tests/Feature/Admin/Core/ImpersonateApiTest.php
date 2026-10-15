<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

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

test('search matches active duty names and returns each user\'s current duties', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole(config('permission.super_admin_role_name'));

    $institution = Institution::factory()->create(['short_name' => ['lt' => 'MIF SA', 'en' => 'MIF SR']]);
    $activeDuty = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Koordinatorė', 'en' => 'Coordinator']]);
    $endedDuty = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Iždininkė', 'en' => 'Treasurer']]);

    $current = User::factory()->create(['name' => 'Ona Dabartinė', 'pronouns' => ['lt' => 'ji/jos', 'en' => 'she/her']]);
    $current->duties()->attach($activeDuty, ['start_date' => now()->subMonth(), 'use_original_duty_name' => true]);

    $former = User::factory()->create(['name' => 'Ieva Buvusi']);
    $former->duties()->attach($endedDuty, ['start_date' => now()->subYear(), 'end_date' => now()->subDay()]);

    asUser($actor)->getJson(route('api.v1.admin.impersonate.search', ['search' => 'Coordinator']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $current->id)
        ->assertJsonPath('data.0.current_duties.0.name', 'Koordinatorė')
        ->assertJsonPath('data.0.current_duties.0.institution', 'MIF SA')
        ->assertJsonPath('data.0.pronouns.lt', 'ji/jos')
        ->assertJsonPath('data.0.current_duties.0.use_original_duty_name', true);

    asUser($actor)->getJson(route('api.v1.admin.impersonate.search', ['search' => 'Treasurer']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
