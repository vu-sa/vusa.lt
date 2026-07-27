<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * `dutiables.duty_id` restricts deletes and membership rows are never removed
 * (ending a membership only stamps `end_date`), so a duty that ever had a member
 * cannot be erased. These tests pin the refusal down to a redirect with an
 * explanation rather than the foreign-key 500 it used to be.
 */

/**
 * @return array{0: User, 1: Role}
 */
function makeDutyAdmin(Tenant $tenant): array
{
    $role = Role::updateOrCreate([
        'name' => 'Duty Force Delete Admin',
        'guard_name' => 'web',
    ]);

    $role->syncPermissions([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'duties.delete.padalinys',
        'duties.forceDelete.padalinys',
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    cache()->flush();

    $admin = makeUser($tenant);
    $admin->duties()->first()->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    cache()->flush();

    return [$admin, $role];
}

function makeTrashedDuty(Tenant $tenant): Duty
{
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $duty->delete();

    return $duty;
}

test('a trashed duty with membership history refuses permanent deletion', function () {
    $tenant = Tenant::query()->first();
    [$admin] = makeDutyAdmin($tenant);
    $duty = makeTrashedDuty($tenant);

    $dutiable = Dutiable::factory()->forDuty($duty)->create();

    asUser($admin)
        ->delete(route('duties.forceDelete', $duty->id))
        ->assertRedirect()
        ->assertSessionHas('error', __('trash.blocked.duty_has_membership_history', ['count' => 1]));

    // The duty stays trashed and the history it guards is untouched.
    $this->assertSoftDeleted('duties', ['id' => $duty->id]);
    $this->assertDatabaseHas('dutiables', ['id' => $dutiable->id]);
});

test('a trashed duty without membership history is permanently deleted', function () {
    $tenant = Tenant::query()->first();
    [$admin] = makeDutyAdmin($tenant);
    $duty = makeTrashedDuty($tenant);

    expect($duty->dutiables()->count())->toBe(0);

    asUser($admin)
        ->delete(route('duties.forceDelete', $duty->id))
        ->assertRedirect()
        ->assertSessionHas('success', __('trash.permanently_deleted'));

    expect(Duty::withTrashed()->find($duty->id))->toBeNull();
});

test('a live duty cannot be permanently deleted even without membership history', function () {
    $tenant = Tenant::query()->first();
    [$admin] = makeDutyAdmin($tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();

    asUser($admin)
        ->delete(route('duties.forceDelete', $duty->id))
        ->assertForbidden();

    $this->assertNotSoftDeleted('duties', ['id' => $duty->id]);
});

test('restoring a duty flashes a translated message', function () {
    $tenant = Tenant::query()->first();
    [$admin] = makeDutyAdmin($tenant);
    $duty = makeTrashedDuty($tenant);

    asUser($admin)
        ->patch(route('duties.restore', $duty->id))
        ->assertRedirect()
        ->assertSessionHas('success', __('trash.restored'));

    $this->assertNotSoftDeleted('duties', ['id' => $duty->id]);
});

describe('force delete blocked reason', function () {
    test('is null for a duty nothing references', function () {
        $duty = Duty::factory()->create();

        expect($duty->forceDeleteBlockedReason())->toBeNull();
    });

    test('counts the membership rows that block deletion', function () {
        $duty = Duty::factory()->create();
        Dutiable::factory()->forDuty($duty)->count(2)->create();

        expect($duty->forceDeleteBlockedReason())
            ->toBe(__('trash.blocked.duty_has_membership_history', ['count' => 2]));
    });

    test('prefers an eager-loaded count over a fresh query', function () {
        $duty = Duty::factory()->create();
        Dutiable::factory()->forDuty($duty)->create();

        $loaded = Duty::withCount('dutiables')->findOrFail($duty->id);

        expect($loaded->forceDeleteBlockedReason())
            ->toBe(__('trash.blocked.duty_has_membership_history', ['count' => 1]))
            ->and($loaded->relationLoaded('dutiables'))->toBeFalse();
    });

    test('is serialized on the admin index so the table can disable the action', function () {
        $tenant = Tenant::query()->first();
        [$admin] = makeDutyAdmin($tenant);
        $duty = makeTrashedDuty($tenant);
        Dutiable::factory()->forDuty($duty)->create();

        $response = asUser($admin)->get(route('duties.index', ['showDeleted' => 'true']));

        $response->assertStatus(200);

        $rows = collect(data_get($response->viewData('page')['props'], 'duties.data'));
        $row = $rows->firstWhere('id', $duty->id);

        expect($row)->not->toBeNull()
            ->and($row['force_delete_blocked_reason'])
            ->toBe(__('trash.blocked.duty_has_membership_history', ['count' => 1]));
    });
});
