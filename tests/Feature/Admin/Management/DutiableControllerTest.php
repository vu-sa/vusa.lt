<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'duties.delete.padalinys',
    ]);

    $this->regularUser = makeUser($this->tenant);
    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Communication Coordinator');

    $this->dutiable = Dutiable::factory()->create([
        'duty_id' => $this->dutyManagerDuty->id,
        'dutiable_id' => $this->regularUser->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay(),
        'additional_email' => null,
    ]);
});

test('cannot update dutiable without permission', function () {
    $unauthorizedUser = User::factory()->create();
    $plainDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $dutiableRecord = Dutiable::factory()->create([
        'duty_id' => $plainDuty->id,
        'dutiable_id' => $unauthorizedUser->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay(),
        'additional_email' => null,
    ]);

    $response = asUser($unauthorizedUser)->patch(route('dutiables.update', $dutiableRecord), [
        'additional_email' => 'test@example.com',
    ]);

    $dutiableRecord->refresh();
    expect($response->status())->toBe(403);
    expect($dutiableRecord->additional_email)->toBeNull();
});

test('duty manager can update dutiable additional_email', function () {
    $response = asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
        'additional_email' => 'kontaktas@example.com',
    ]);

    $response->assertRedirect();

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBe('kontaktas@example.com');
});

test('returns json response for api requests', function () {
    $response = asUser($this->dutyManager)
        ->withHeader('Accept', 'application/json')
        ->patch(route('dutiables.update', $this->dutiable), [
            'additional_email' => 'api@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Pareigybės el. paštas sėkmingai atnaujintas!',
        ]);

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBe('api@example.com');
});

test('can clear additional_email by sending null', function () {
    $this->dutiable->update(['additional_email' => 'existing@example.com']);

    $response = asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
        'additional_email' => null,
    ]);

    $response->assertRedirect();

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBeNull();
});

test('cannot update dutiable without additional_email field', function () {
    $response = asUser($this->dutyManager)->patch(route('dutiables.update', $this->dutiable), [
        'start_date' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect();

    $this->dutiable->refresh();
    expect($this->dutiable->additional_email)->toBeNull();
});

test('update request authorizes using dutiable duty relation', function () {
    $response = asUser($this->dutyManager)
        ->withHeader('Accept', 'application/json')
        ->patch(route('dutiables.update', $this->dutiable), [
            'additional_email' => 'authorized@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});
