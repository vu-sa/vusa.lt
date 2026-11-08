<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('assigning oneself requires grantable responsibilities on every entry point', function (string $entry): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $duty->assignRole(Role::factory()->create());
    $existingDutyIds = $actor->current_duties()->pluck('duties.id')->all();

    $client = asUser($actor);
    $response = match ($entry) {
        'assignment' => $client->postJson(route('dutiables.store'), ['duty_id' => $duty->id, 'user_id' => $actor->id]),
        'duty' => $client->patchJson(route('duties.update', $duty), [
            'name' => $duty->getTranslations('name'), 'institution_id' => $duty->institution_id,
            'contacts_grouping' => 'none', 'current_users' => [$actor->id],
        ]),
        'user' => $client->patchJson(route('users.update', $actor), [
            'name' => $actor->name, 'current_duties' => [...$existingDutyIds, $duty->id],
        ]),
        'batch' => $client->postJson(route('duties.batchUpdateUsers', $duty), [
            'user_changes' => [['user_id' => $actor->id, 'action' => 'add']],
        ]),
    };

    $response->assertUnprocessable()->assertJsonValidationErrors('user_id')
        ->assertJsonPath('errors.user_id.0', __('dutiables.assign.self_responsibility'));
    expect($duty->users()->whereKey($actor->id)->exists())->toBeFalse();
})->with(['assignment', 'duty', 'user', 'batch']);

test('a coordinator can assign another member to an ordinary responsibility they do not hold', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $member = User::factory()->create();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $duty->assignRole(Role::factory()->create());

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $duty->id, 'user_id' => $member->id])
        ->assertRedirect();

    expect($duty->current_users()->whereKey($member->id)->exists())->toBeTrue();
});

test('a coordinator can assign themselves to a responsibility they hold', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $duty->assignRole('Komunikacijos koordinatorius');

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $duty->id, 'user_id' => $actor->id])
        ->assertRedirect();

    expect($duty->current_users()->whereKey($actor->id)->exists())->toBeTrue();
});

test('global responsibilities require a responsibility administrator', function (bool $superRole): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $member = User::factory()->create();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $role = $superRole ? Role::findByName(config('permission.super_admin_role_name')) : Role::factory()->create();
    if (! $superRole) {
        $role->givePermissionTo('news.read.*');
    }
    $duty->assignRole($role);

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $duty->id, 'user_id' => $member->id])
        ->assertUnprocessable()->assertJsonValidationErrors('user_id')
        ->assertJsonPath('errors.user_id.0', __('dutiables.assign.global_responsibility'));

    expect($duty->users()->whereKey($member->id)->exists())->toBeFalse();
})->with(['super admin responsibility' => true, 'global permission' => false]);

test('a responsibility administrator can assign another member to a global responsibility', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $manager = Role::factory()->create();
    $manager->givePermissionTo('roles.update.*');
    $actor->duties()->first()->assignRole($manager);
    $member = User::factory()->create();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $duty->assignRole(Role::findByName(config('permission.super_admin_role_name')));

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $duty->id, 'user_id' => $member->id])
        ->assertRedirect();

    expect($duty->current_users()->whereKey($member->id)->exists())->toBeTrue();
});

test('saving an existing roster leaves its responsibilities and assignments alone', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $row = Dutiable::factory()->forDuty($duty)->forUser($actor)->active()->create();
    $duty->assignRole(Role::factory()->create());

    asUser($actor)->patchJson(route('duties.update', $duty), [
        'name' => $duty->getTranslations('name'), 'institution_id' => $duty->institution_id,
        'contacts_grouping' => 'none', 'current_users' => [$actor->id],
    ])->assertRedirect();

    expect($row->fresh()->end_date)->toBeNull();
});

test('a coordinator cannot assign a member in another tenant without a delegated seat', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $member = User::factory()->create();
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first()))->create();

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $duty->id, 'user_id' => $member->id])
        ->assertForbidden();

    expect($duty->users()->whereKey($member->id)->exists())->toBeFalse();
});

test('tenant roster synchronization also checks self assignments', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $duty->assignableTenants()->attach($tenant);
    $duty->assignRole(Role::factory()->create());

    asUser($actor)->patchJson(route('duties.update', $duty), [
        'name' => $duty->getTranslations('name'), 'institution_id' => $duty->institution_id,
        'contacts_grouping' => 'none',
        'assignable_tenants' => [['tenant_id' => $tenant->id, 'user_ids' => [$actor->id]]],
    ])->assertUnprocessable()->assertJsonValidationErrors('user_id');

    expect($duty->users()->whereKey($actor->id)->exists())->toBeFalse();
});

test('a moved duty uses its destination responsibilities when assigning members', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $destination = Institution::factory()->for($tenant)->create();
    ResponsibilityGrant::factory()->forWholeInstitution($destination)->of(Role::factory()->create())->create();

    asUser($actor)->patchJson(route('duties.update', $duty), [
        'name' => $duty->getTranslations('name'), 'institution_id' => $destination->id,
        'contacts_grouping' => 'none', 'current_users' => [$actor->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('user_id');

    expect($duty->fresh()->institution_id)->toBe($duty->institution_id)
        ->and($duty->users()->whereKey($actor->id)->exists())->toBeFalse();
});

test('derived seats apply the same responsibility limits as manual seats', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $member = User::factory()->create();
    $source = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $target = Duty::factory()->for($source->institution)->create();
    $role = Role::factory()->create();
    $role->givePermissionTo('news.read.*');
    $target->assignRole($role);
    $source->exOfficioTargetDuties()->attach($target);

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $source->id, 'user_id' => $member->id])
        ->assertUnprocessable()->assertJsonValidationErrors('user_id');

    expect($source->users()->whereKey($member->id)->exists())->toBeFalse()
        ->and($target->users()->whereKey($member->id)->exists())->toBeFalse();
});

test('ordinary derived seats can be reconciled for another member without holding their responsibilities', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $member = User::factory()->create();
    $source = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $target = Duty::factory()->for($source->institution)->create();
    $target->assignRole(Role::factory()->create());
    $source->exOfficioTargetDuties()->attach($target);

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $source->id, 'user_id' => $member->id])
        ->assertRedirect();

    $root = $source->dutiables()->where('dutiable_id', $member->id)->sole();
    expect($root->derivedDutiables()->sole()->duty_id)->toBe($target->id);
});

test('a new derived seat for the actor requires grantable responsibilities and returns 422', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $source = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $target = Duty::factory()->for($source->institution)->create();
    $target->assignRole(Role::factory()->create());
    $source->exOfficioTargetDuties()->attach($target);

    asUser($actor)->postJson(route('dutiables.store'), ['duty_id' => $source->id, 'user_id' => $actor->id])
        ->assertUnprocessable()->assertJsonPath('errors.user_id.0', __('dutiables.assign.self_responsibility'));

    expect($source->users()->whereKey($actor->id)->exists())->toBeFalse()
        ->and($target->users()->whereKey($actor->id)->exists())->toBeFalse();
});

test('saving an existing scheduled membership does not apply the new assignment ceiling', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $row = Dutiable::factory()->forDuty($duty)->forUser($actor)->create([
        'start_date' => today()->addDay(), 'end_date' => null,
    ]);
    $duty->assignRole(Role::factory()->create());

    asUser($actor)->patchJson(route('dutiables.update', $row), ['additional_email' => 'member@example.com'])
        ->assertRedirect();

    expect($row->fresh()->additional_email)->toBe('member@example.com')
        ->and($row->fresh()->end_date)->toBeNull();
});

test('resuming an own membership is allowed when its responsibilities are already held', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $role = Role::factory()->create();
    $actor->duties()->firstOrFail()->assignRole($role);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $row = Dutiable::factory()->forDuty($duty)->forUser($actor)->ended()->create();
    $duty->assignRole($role);

    asUser($actor)->postJson(route('duties.batchUpdateUsers', $duty), [
        'user_changes' => [['user_id' => $actor->id, 'action' => 'add', 'start_date' => today()->toDateString(), 'end_date' => null]],
    ])->assertRedirect();

    expect($row->fresh()->end_date)->toBeNull();
});

test('resuming an ended term requires the current responsibility ceiling', function (): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $member = User::factory()->create();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $row = Dutiable::factory()->forDuty($duty)->forUser($member)->ended()->create();
    $role = Role::factory()->create();
    $role->givePermissionTo('news.read.*');
    $duty->assignRole($role);

    asUser($actor)->postJson(route('duties.batchUpdateUsers', $duty), [
        'user_changes' => [['user_id' => $member->id, 'action' => 'add', 'start_date' => today()->toDateString(), 'end_date' => null]],
    ])->assertUnprocessable()->assertJsonValidationErrors('user_id');

    expect($row->fresh()->end_date->lt(today()))->toBeTrue();
});
