<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
});

function makeUnclaimedUser(): User
{
    return User::factory()->create();
}

function attachUserDuty(User $user, Tenant $tenant, array $pivot = []): void
{
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();

    $user->duties()->attach($duty, $pivot);
}

test('a duty-less user is absent from the tenant user index', function (): void {
    $unclaimed = makeUnclaimedUser();

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->where('users.data', fn ($users) => ! collect($users)->contains('id', $unclaimed->id))
        );
});

test('a duty-less user is absent from the global user index', function (): void {
    $unclaimed = makeUnclaimedUser();

    asUser(makeAdminUser($this->tenant))
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => ! collect($users)->contains('id', $unclaimed->id))
        );
});

test('a claimed user from another tenant stays hidden', function (): void {
    $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => ! collect($users)->contains('id', $foreign->id))
        );
});

test('a current tenant duty makes a user visible and editable', function (): void {
    $user = User::factory()->create();
    attachUserDuty($user, $this->tenant, ['start_date' => now()->subDay()]);

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => collect($users)->contains('id', $user->id))
        );

    asUser($this->coordinator)->get(route('users.edit', $user))->assertOk();
});

test('a previous tenant duty keeps a user visible and editable', function (): void {
    $user = User::factory()->create();
    attachUserDuty($user, $this->tenant, [
        'start_date' => now()->subYear(),
        'end_date' => now()->subDay(),
    ]);

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => collect($users)->contains('id', $user->id))
        );

    asUser($this->coordinator)->get(route('users.edit', $user))->assertOk();

    asUser($this->coordinator)
        ->patch(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+370 600 00005',
            'current_duties' => [],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->fresh()->phone)->toBe('+370 600 00005');
});

test('a scheduled tenant duty makes a user visible and editable', function (): void {
    $user = User::factory()->create();
    attachUserDuty($user, $this->tenant, [
        'start_date' => now()->addMonth(),
        'end_date' => now()->addYear(),
    ]);

    asUser($this->coordinator)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => collect($users)->contains('id', $user->id))
        );

    asUser($this->coordinator)->get(route('users.edit', $user))->assertOk();
});

test('a search does not bring an unclaimed user back into the index', function (): void {
    $unclaimed = makeUnclaimedUser();
    $unclaimed->update(['name' => 'Zigmas Zigmaitis']);

    asUser($this->coordinator)
        ->get(route('users.index', ['search' => 'Zigmas']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => ! collect($users)->contains('id', $unclaimed->id))
        );
});

test('a tenant admin cannot open or update a duty-less user directly', function (): void {
    $unclaimed = makeUnclaimedUser();

    asUser($this->coordinator)
        ->get(route('users.show', $unclaimed))
        ->assertForbidden();

    asUser($this->coordinator)
        ->get(route('users.edit', $unclaimed))
        ->assertForbidden();

    asUser($this->coordinator)
        ->patch(route('users.update', $unclaimed), [
            'name' => $unclaimed->name,
            'email' => $unclaimed->email,
            'phone' => '+370 622 22222',
            'current_duties' => [],
        ])
        ->assertForbidden();

    expect($unclaimed->fresh()->phone)->not->toBe('+370 622 22222');
});

test('a tenant admin cannot assign a duty to an unclaimed user through user editing', function (): void {
    $unclaimed = makeUnclaimedUser();
    $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

    asUser($this->coordinator)
        ->patch(route('users.update', $unclaimed), [
            'name' => $unclaimed->name,
            'email' => $unclaimed->email,
            'current_duties' => [$duty->id],
        ])
        ->assertForbidden();

    expect($unclaimed->fresh()->duties()->whereKey($duty->id)->exists())->toBeFalse();
});

test('a tenant admin cannot delete a duty-less user', function (): void {
    $unclaimed = makeUnclaimedUser();

    asUser($this->coordinator)
        ->delete(route('users.destroy', $unclaimed))
        ->assertForbidden();

    expect($unclaimed->fresh()->trashed())->toBeFalse();
});

test('a duty-less super admin stays available to another super admin', function (): void {
    $target = makeUnclaimedUser();
    $target->assignRole(config('permission.super_admin_role_name'));
    $actor = makeAdminUser($this->tenant);

    asUser($actor)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => collect($users)->contains('id', $target->id))
        );
});
