<?php

use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.update.padalinys',
    ]);

    $this->admin = makeUser($this->tenant);
    $this->adminDuty = $this->admin->duties()->first();
    $this->adminDuty->assignRole('Communication Coordinator');

    $this->adminDutiable = Dutiable::where('duty_id', $this->adminDuty->id)
        ->where('dutiable_id', $this->admin->id)
        ->where('dutiable_type', User::class)
        ->first();
});

function batchRemoveSelfPayload(User $admin, array $overrides = []): array
{
    return array_merge([
        'user_changes' => [
            [
                'user_id' => (string) $admin->id,
                'action' => 'remove',
                'end_date' => now()->toDateString(),
            ],
        ],
    ], $overrides);
}

test('removing yourself from your own admin-granting duty is warned and rolled back', function () {
    asUserWithInertia($this->admin)
        ->post(route('duties.batchUpdateUsers', $this->adminDuty), batchRemoveSelfPayload($this->admin))
        ->assertSessionHas('access_change_warning');

    expect($this->adminDutiable->fresh()->end_date)->toBeNull();
});

test('acknowledged self-removal proceeds', function () {
    asUserWithInertia($this->admin)
        ->post(route('duties.batchUpdateUsers', $this->adminDuty), batchRemoveSelfPayload($this->admin, [
            'acknowledge_access_change' => true,
        ]))
        ->assertSessionHas('success');

    expect($this->adminDutiable->fresh()->end_date)->not->toBeNull();
});

test('removing another user is not guarded', function () {
    $other = makeUser($this->tenant);
    $this->adminDuty->users()->attach($other->id, ['start_date' => now()->subDay()]);

    asUserWithInertia($this->admin)
        ->post(route('duties.batchUpdateUsers', $this->adminDuty), batchRemoveSelfPayload($other))
        ->assertSessionMissing('access_change_warning');

    $otherDutiable = Dutiable::where('duty_id', $this->adminDuty->id)
        ->where('dutiable_id', $other->id)
        ->where('dutiable_type', User::class)
        ->first();

    expect($otherDutiable->end_date)->not->toBeNull();
});
