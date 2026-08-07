<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

/**
 * GitHub issue #249. A user's tenants are derived from their duties, so a user with
 * no duties belongs to no tenant and is filtered out of the index and refused by
 * every policy — including for the admin who just created them and now needs to
 * assign the very duty that would make them visible.
 *
 * Such a record carries no authority of its own, so any tenant admin may claim it.
 * The exception is a user holding a directly assigned role: AdminSeeder creates a
 * duty-less Super Admin in every dev and CI database.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
});

function makeUnclaimedUser(): User
{
    return User::factory()->create();
}

test('a duty-less user appears in the index for a tenant admin', function (): void {
    $unclaimed = makeUnclaimedUser();

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->where('users.data', fn ($users) => collect($users)->contains('id', $unclaimed->id))
        );
});

test('a claimed user from another tenant stays hidden', function (): void {
    $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => ! collect($users)->contains('id', $foreign->id))
        );
});

test('the escape hatch still respects an active search filter', function (): void {
    // The tenant constraint and the escape hatch must be ORed inside one nested
    // group. A top-level orWhere would let every duty-less user past the search.
    $unclaimed = makeUnclaimedUser();
    $unclaimed->update(['name' => 'Zigmas Zigmaitis']);

    asUser($this->coordinator)
        ->get(route('users.index', ['search' => 'Definitely Not Zigmas']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => ! collect($users)->contains('id', $unclaimed->id))
        );
});

test('a tenant admin can open and edit a duty-less user', function (): void {
    $unclaimed = makeUnclaimedUser();

    asUser($this->coordinator)
        ->get(route('users.edit', $unclaimed))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->component('Admin/People/EditUser'));

    asUser($this->coordinator)
        ->patch(route('users.update', $unclaimed), [
            'name' => $unclaimed->name,
            'email' => $unclaimed->email,
            'phone' => '+370 622 22222',
            'current_duties' => [],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($unclaimed->fresh()->phone)->toBe('+370 622 22222');
});

test('a tenant admin can assign one of their own duties to a duty-less user', function (): void {
    // The whole point of the fix: an unclaimed user must be reachable long enough
    // for somebody to give them a unit.
    $unclaimed = makeUnclaimedUser();
    $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

    asUser($this->coordinator)
        ->patch(route('users.update', $unclaimed), [
            'name' => $unclaimed->name,
            'email' => $unclaimed->email,
            'current_duties' => [$duty->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($unclaimed->fresh()->duties()->whereKey($duty->id)->exists())->toBeTrue();
});

describe('the carve-out for directly assigned roles', function (): void {
    test('a duty-less super admin is not listed', function (): void {
        $superAdmin = makeUnclaimedUser();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        asUser($this->coordinator)
            ->get(route('users.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.data', fn ($users) => ! collect($users)->contains('id', $superAdmin->id))
            );
    });

    test('a duty-less super admin cannot be edited', function (): void {
        $superAdmin = makeUnclaimedUser();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        asUser($this->coordinator)
            ->get(route('users.edit', $superAdmin))
            ->assertStatus(403);
    });

    test('a duty-less super admin cannot be deleted', function (): void {
        $superAdmin = makeUnclaimedUser();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        asUser($this->coordinator)
            ->delete(route('users.destroy', $superAdmin))
            ->assertStatus(403);

        expect($superAdmin->fresh()->trashed())->toBeFalse();
    });
});
