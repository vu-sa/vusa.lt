<?php

use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\Meeting;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('meeting attachment refuses institutions outside the offered relationship list', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $own = $actor->duties()->first()->institution;
    $meeting = Meeting::factory()->hasAttached($own)->create(['start_time' => now()]);
    $foreign = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();

    asUser($actor)->postJson(route('meetings.institutions.attach', $meeting), ['institution_id' => $foreign->id])
        ->assertUnprocessable()->assertJsonValidationErrors('institution_id');

    expect($meeting->institutions()->pluck('institutions.id')->all())->toBe([$own->id]);
});

test('meeting attachment accepts an institution offered through the relationship graph', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $own = $actor->duties()->first()->institution;
    $meeting = Meeting::factory()->hasAttached($own)->create(['start_time' => now()]);
    $related = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    InstitutionLink::factory()->create(['source_institution_id' => $own->id, 'target_institution_id' => $related->id]);

    asUser($actor)->postJson(route('meetings.institutions.attach', $meeting), ['institution_id' => $related->id])
        ->assertRedirect();

    expect($meeting->institutions()->whereKey($related->id)->exists())->toBeTrue();
});

test('meeting attachment requires update access to the meeting', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeUser($tenant);
    $other = Institution::factory()->for($tenant)->create();
    $meeting = Meeting::factory()->hasAttached($other)->create(['start_time' => now()]);

    asUser($actor)->postJson(route('meetings.institutions.attach', $meeting), [
        'institution_id' => $actor->duties()->first()->institution_id,
    ])->assertForbidden();
});

test('detaching the last institution that grants update access is refused', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $own = $actor->duties()->first()->institution;
    $foreign = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    $meeting = Meeting::factory()->hasAttached($own)->hasAttached($foreign)->create(['start_time' => now()]);

    asUser($actor)->deleteJson(route('meetings.institutions.detach', [$meeting, $own]))->assertForbidden();

    expect($meeting->institutions()->count())->toBe(2);
});

test('detaching an institution keeps the meeting editable through a remaining institution', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $own = $actor->duties()->first()->institution;
    $foreign = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    $meeting = Meeting::factory()->hasAttached($own)->hasAttached($foreign)->create(['start_time' => now()]);

    asUser($actor)->deleteJson(route('meetings.institutions.detach', [$meeting, $foreign]))->assertRedirect();

    expect($meeting->institutions()->pluck('institutions.id')->all())->toBe([$own->id]);
});

test('global meeting updaters may detach their own institution', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeUser($tenant);
    $role = Role::factory()->create();
    $role->givePermissionTo('meetings.update.*');
    $actor->duties()->first()->assignRole($role);
    $own = $actor->duties()->first()->institution;
    $other = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    $meeting = Meeting::factory()->hasAttached($own)->hasAttached($other)->create(['start_time' => now()]);

    asUser($actor)->deleteJson(route('meetings.institutions.detach', [$meeting, $own]))->assertRedirect();

    expect($meeting->institutions()->pluck('institutions.id')->all())->toBe([$other->id]);
});

test('own meeting permissions cannot preserve access after their institution is detached', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeUser($tenant);
    $role = Role::factory()->create();
    $role->givePermissionTo('meetings.update.own');
    $actor->duties()->first()->assignRole($role);
    $own = $actor->duties()->first()->institution;
    $other = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    $meeting = Meeting::factory()->hasAttached($own)->hasAttached($other)->create(['start_time' => now()->subMonth()]);

    asUser($actor)->deleteJson(route('meetings.institutions.detach', [$meeting, $own]))->assertForbidden();

    expect($meeting->institutions()->count())->toBe(2);
});
