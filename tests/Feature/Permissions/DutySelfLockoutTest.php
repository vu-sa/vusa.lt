<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
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
});

function dutyUpdatePayload(Duty $duty, array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Duty',
        'institution_id' => $duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
    ], $overrides);
}

test('removing the role from your own duty is warned and rolled back', function () {
    asUserWithInertia($this->admin)
        ->patch(route('duties.update', $this->adminDuty), dutyUpdatePayload($this->adminDuty, [
            'roles' => [],
            'current_users' => [$this->admin->id],
        ]))
        ->assertSessionHas('access_change_warning');

    expect($this->adminDuty->fresh()->roles()->count())->toBeGreaterThan(0);
});

test('editing a duty you do not hold is not guarded', function () {
    $otherDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $otherDuty->assignRole('Communication Coordinator');

    asUserWithInertia($this->admin)
        ->patch(route('duties.update', $otherDuty), dutyUpdatePayload($otherDuty, [
            'roles' => [],
        ]))
        ->assertSessionMissing('access_change_warning');

    expect($otherDuty->fresh()->roles()->count())->toBe(0);
});
