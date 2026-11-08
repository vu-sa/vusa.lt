<?php

use App\Actions\UpdateDuty;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

pest()->use(RefreshDatabase::class);

test('duty update refuses a destination outside the update scope', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();

    asUser($actor)->patchJson(route('duties.update', $duty), [
        'name' => $duty->getTranslations('name'),
        'institution_id' => $destination->id,
        'contacts_grouping' => 'none',
    ])->assertUnprocessable()->assertJsonValidationErrors('institution_id');

    expect($duty->fresh()->institution_id)->toBe($duty->institution_id);
});

test('duty update accepts a destination in the update scope', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for($tenant)->create();

    asUser($actor)->patchJson(route('duties.update', $duty), [
        'name' => $duty->getTranslations('name'),
        'institution_id' => $destination->id,
        'contacts_grouping' => 'none',
    ])->assertRedirect();

    expect($duty->fresh()->institution_id)->toBe($destination->id);
});

test('duty writes require a live institution', function (string $operation): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for($tenant)->create();
    $destination->delete();
    $data = ['name' => ['lt' => 'Pareigos', 'en' => 'Duty'], 'institution_id' => $destination->id, 'contacts_grouping' => 'none'];

    $response = $operation === 'store'
        ? asUser($actor)->postJson(route('duties.store'), $data)
        : asUser($actor)->patchJson(route('duties.update', $duty), $data);

    $response->assertUnprocessable()->assertJsonValidationErrors('institution_id');
    expect($duty->fresh()->institution_id)->toBe($duty->institution_id);
})->with(['store', 'update']);

test('the duty action also refuses a move outside the update scope', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    asUser($actor);

    expect(fn () => app(UpdateDuty::class)->execute($duty, [
        'name' => ['lt' => 'Pakeista', 'en' => 'Changed'],
        'institution_id' => $destination->id,
        'contacts_grouping' => 'none',
    ]))->toThrow(ValidationException::class)
        ->and($duty->fresh()->institution_id)->toBe($duty->institution_id);
});

test('a member without duty update access receives 403', function (): void {
    $tenant = Tenant::query()->first();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();

    asUser(makeUser($tenant))->patchJson(route('duties.update', $duty), [])->assertForbidden();
});

test('duty creation refuses institutions outside the creation scope', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $institution = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    $before = Duty::count();

    asUser($actor)->postJson(route('duties.store'), [
        'name' => ['lt' => 'Pareigos', 'en' => 'Duty'],
        'institution_id' => $institution->id, 'contacts_grouping' => 'none',
    ])->assertUnprocessable()->assertJsonValidationErrors('institution_id');

    expect(Duty::count())->toBe($before);
});

test('global duty update scope permits moving between tenants', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeUser($tenant);
    $role = Role::factory()->create();
    $role->givePermissionTo('duties.update.*');
    $actor->duties()->first()->assignRole($role);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();

    asUser($actor)->patchJson(route('duties.update', $duty), [
        'name' => $duty->getTranslations('name'), 'institution_id' => $destination->id, 'contacts_grouping' => 'none',
    ])->assertRedirect();

    expect($duty->fresh()->institution_id)->toBe($destination->id);
});

test('the duty action refuses a trashed destination even with global access', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeAdminUser($tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for($tenant)->create();
    $destination->delete();
    asUser($actor);

    expect(fn () => app(UpdateDuty::class)->execute($duty, [
        'name' => ['lt' => 'Pakeista', 'en' => 'Changed'],
        'institution_id' => $destination->id, 'contacts_grouping' => 'none',
    ]))->toThrow(ValidationException::class)
        ->and($duty->fresh()->institution_id)->toBe($duty->institution_id);
});

test('moving a duty without an actor returns a validation error', function (): void {
    $tenant = Tenant::query()->first();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for($tenant)->create();

    expect(fn () => app(UpdateDuty::class)->execute($duty, ['institution_id' => $destination->id]))
        ->toThrow(ValidationException::class)
        ->and($duty->fresh()->institution_id)->toBe($duty->institution_id);
});
