<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Deleting a person record is a global act — the record is shared by every tenant
 * they have ever served. UserPolicy therefore requires full tenant *containment*
 * for delete/restore/forceDelete, rather than the any-overlap rule that governs
 * ordinary edits, and refuses outright for users holding a directly assigned role.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
});

/** Give the user an additional duty in a newly created, unrelated tenant. */
function giveDestructiveTestDutyElsewhere(User $user): void
{
    $user->duties()->attach(
        Duty::factory()->for(
            Institution::factory()->for(Tenant::factory()->create(['type' => 'padalinys']))
        )->create(),
        ['start_date' => now()->subDay()],
    );
}

test('coordinator can delete a fully contained user with no direct role', function (): void {
    $target = makeUser($this->tenant);

    asUser($this->coordinator)
        ->delete(route('users.destroy', $target))
        ->assertRedirect();

    expect($target->fresh()->trashed())->toBeTrue();
});

test('coordinator cannot delete a user who also belongs to another tenant', function (): void {
    $target = makeUser($this->tenant);
    giveDestructiveTestDutyElsewhere($target);

    asUser($this->coordinator)
        ->delete(route('users.destroy', $target))
        ->assertStatus(403);

    expect($target->fresh()->trashed())->toBeFalse();
});

test('coordinator cannot delete a super admin attached to their own tenant', function (): void {
    // The scenario in full: attach a super admin to one of your duties, then remove
    // them. Containment alone would not stop this, because the super admin may hold
    // no other duty.
    $target = makeUser($this->tenant);
    $target->assignRole(config('permission.super_admin_role_name'));

    asUser($this->coordinator)
        ->delete(route('users.destroy', $target))
        ->assertStatus(403);

    expect($target->fresh()->trashed())->toBeFalse();
});

test('coordinator cannot delete a user holding any directly assigned role', function (): void {
    $target = makeUser($this->tenant);
    $target->assignRole(Role::firstOrCreate(['name' => 'Directly Assigned', 'guard_name' => 'web']));

    asUser($this->coordinator)
        ->delete(route('users.destroy', $target))
        ->assertStatus(403);

    expect($target->fresh()->trashed())->toBeFalse();
});

test('an admin cannot delete themselves', function (): void {
    asUser($this->coordinator)
        ->delete(route('users.destroy', $this->coordinator))
        ->assertStatus(403);

    expect($this->coordinator->fresh()->trashed())->toBeFalse();
});

test('even a super admin cannot delete themselves', function (): void {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)
        ->delete(route('users.destroy', $admin))
        ->assertStatus(403);

    expect($admin->fresh()->trashed())->toBeFalse();
});

test('a super admin can delete a user spanning several tenants', function (): void {
    $admin = makeAdminUser($this->tenant);
    $target = makeUser($this->tenant);
    giveDestructiveTestDutyElsewhere($target);

    asUser($admin)
        ->delete(route('users.destroy', $target))
        ->assertRedirect();

    expect($target->fresh()->trashed())->toBeTrue();
});

describe('restore follows the same containment rule', function (): void {
    test('coordinator can restore a fully contained user', function (): void {
        $target = makeUser($this->tenant);
        $target->delete();

        asUser($this->coordinator)
            ->patch(route('users.restore', $target))
            ->assertRedirect();

        expect($target->fresh()->trashed())->toBeFalse();
    });

    test('coordinator cannot restore a user who also belongs to another tenant', function (): void {
        $target = makeUser($this->tenant);
        giveDestructiveTestDutyElsewhere($target);
        $target->delete();

        asUser($this->coordinator)
            ->patch(route('users.restore', $target))
            ->assertStatus(403);

        expect($target->fresh()->trashed())->toBeTrue();
    });

    test('coordinator cannot restore a super admin', function (): void {
        $target = makeUser($this->tenant);
        $target->assignRole(config('permission.super_admin_role_name'));
        $target->delete();

        asUser($this->coordinator)
            ->patch(route('users.restore', $target))
            ->assertStatus(403);

        expect($target->fresh()->trashed())->toBeTrue();
    });
});

describe('force delete does not strip duties before it is allowed to run', function (): void {
    test('a refused force delete leaves the duty assignments intact', function (): void {
        // The detach used to run before the blocker check. On the refused path that
        // left the user with no duties — and therefore no tenants — which made them
        // invisible to every tenant admin for good.
        $admin = makeAdminUser($this->tenant);
        $target = makeUser($this->tenant);
        $dutyCount = $target->duties()->count();

        InstitutionCheckIn::factory()->create(['user_id' => $target->id]);
        $target->delete();

        asUser($admin)
            ->delete(route('users.forceDelete', $target))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect(User::withTrashed()->find($target->id))->not->toBeNull()
            ->and($target->duties()->count())->toBe($dutyCount);
    });

    test('an allowed force delete removes the user and their duty assignments', function (): void {
        $admin = makeAdminUser($this->tenant);
        $target = makeUser($this->tenant);
        $target->delete();

        asUser($admin)
            ->delete(route('users.forceDelete', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect(User::withTrashed()->find($target->id))->toBeNull();
        $this->assertDatabaseMissing('dutiables', ['dutiable_id' => $target->id]);
    });
});
