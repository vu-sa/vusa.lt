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

test('destroying own admin-granting dutiable is warned and nothing persists', function () {
    asUserWithInertia($this->admin)
        ->delete(route('dutiables.destroy', $this->adminDutiable))
        ->assertSessionHas('access_change_warning');

    expect(Dutiable::find($this->adminDutiable->id))->not->toBeNull();
});

test('acknowledged destroy proceeds', function () {
    asUserWithInertia($this->admin)
        ->delete(route('dutiables.destroy', $this->adminDutiable), [
            'acknowledge_access_change' => true,
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect(Dutiable::find($this->adminDutiable->id))->toBeNull();
});

test('destroying another users dutiable is not guarded', function () {
    $other = makeUser($this->tenant);
    $otherDutiable = Dutiable::where('dutiable_id', $other->id)
        ->where('dutiable_type', User::class)
        ->first();

    asUserWithInertia($this->admin)
        ->delete(route('dutiables.destroy', $otherDutiable))
        ->assertSessionMissing('access_change_warning');

    expect(Dutiable::find($otherDutiable->id))->toBeNull();
});
