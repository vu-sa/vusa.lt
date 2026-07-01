<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\Permissions\AccessChangeAnalyzer;
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

    $this->analyzer = app(AccessChangeAnalyzer::class);
});

test('end-dating the actor\'s only role-granting duty reports the lost role and rolls back', function () {
    $report = $this->analyzer->apply($this->admin, function () {
        $this->admin->duties()->updateExistingPivot($this->adminDuty->id, ['end_date' => now()->subDay()]);
    });

    expect($report->isCritical())->toBeTrue()
        ->and($report->lostRoles)->toContain('Communication Coordinator');

    // Rolled back: the duty is still active for the user.
    $stillActive = Dutiable::where('duty_id', $this->adminDuty->id)
        ->where('dutiable_id', $this->admin->id)
        ->whereNull('end_date')
        ->exists();

    expect($stillActive)->toBeTrue();
});

test('a change that removes no role is committed', function () {
    $report = $this->analyzer->apply($this->admin, function () {
        $this->admin->update(['name' => 'Changed Name']);
    });

    expect($report->isCritical())->toBeFalse()
        ->and($report->lostRoles)->toBe([])
        ->and($report->severity())->toBe('none')
        ->and($this->admin->fresh()->name)->toBe('Changed Name');
});

test('a role retained through another current duty is not reported lost', function () {
    // A second current duty carrying the same role.
    $secondDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $secondDuty->assignRole('Communication Coordinator');
    $this->admin->duties()->attach($secondDuty->id, ['start_date' => now()->subDay()]);

    $report = $this->analyzer->apply($this->admin, function () {
        $this->admin->duties()->updateExistingPivot($this->adminDuty->id, ['end_date' => now()->subDay()]);
    });

    expect($report->lostRoles)->toBe([])
        ->and($report->isCritical())->toBeFalse();

    // Committed: the first duty is now end-dated.
    $stillActive = Dutiable::where('duty_id', $this->adminDuty->id)
        ->where('dutiable_id', $this->admin->id)
        ->whereNull('end_date')
        ->exists();

    expect($stillActive)->toBeFalse();
});

test('removing the super admin role reports it lost', function () {
    $superAdmin = makeUser($this->tenant);
    $superAdmin->assignRole(config('permission.super_admin_role_name'));

    $report = $this->analyzer->apply($superAdmin, function () use ($superAdmin) {
        $superAdmin->roles()->sync([]);
    });

    expect($report->isCritical())->toBeTrue()
        ->and($report->lostRoles)->toContain(config('permission.super_admin_role_name'))
        ->and($superAdmin->fresh()->isSuperAdmin())->toBeTrue(); // rolled back
});
