<?php

/**
 * `own` reaches only what `PermissionCatalog::ownRelation()` names. It used to follow any Duty relation
 * named like the resource, so a stored `reservations.update.own` reached, through Duty::reservations(),
 * the reservations of everyone who ever held the duty.
 */

use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->holder = makeUser(Tenant::query()->first());
    $this->duty = $this->holder->duties()->first();

    $this->formerHolder = User::factory()->create();
    $this->duty->users()->attach($this->formerHolder, ['start_date' => now()->subYears(2), 'end_date' => now()->subYear()]);

    $this->reservation = Reservation::factory()->create();
    $this->reservation->users()->attach($this->formerHolder);
});

test('a stored own permission without a catalog meaning reaches nobody else\'s records', function (string $ability): void {
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::firstOrCreate(['name' => "reservations.{$ability}.own", 'guard_name' => 'web']));
    $this->duty->assignRole($role);

    expect($this->holder->can($ability, $this->reservation))->toBeFalse();
})->with(['update', 'delete']);

test('own still reaches the records its catalog relation names', function (): void {
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'dutiables.update.own', 'guard_name' => 'web']));
    $this->duty->assignRole($role);

    $formerTerm = Dutiable::query()->where('duty_id', $this->duty->id)->where('dutiable_id', $this->formerHolder->id)->firstOrFail();

    expect($this->holder->can('update', $formerTerm))->toBeTrue();
});
