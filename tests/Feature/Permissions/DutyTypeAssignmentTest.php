<?php

use App\Actions\GetAttachableTypesForDuty;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\ResponsibilityResolver;
use Database\Factories\RoleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->actor = makeUser($this->tenant);
    $this->actorDuty = $this->actor->duties()->firstOrFail();
    $this->manager = Role::factory()->create();
    $this->manager->givePermissionTo(['duties.create.padalinys', 'duties.update.padalinys', 'dutyTypes.read.*', 'dutyTypes.update.*']);
    $this->actorDuty->assignRole($this->manager);
    $this->type = DutyType::factory()->create();
    $this->privileged = Role::factory()->create();
    $this->privileged->givePermissionTo('news.delete.*');
    ResponsibilityGrant::factory()->forDutyType($this->type)->of($this->privileged)->create();
});

function dutyTypeAssignmentPayload(Duty $duty, array $types): array
{
    return [
        'name' => ['lt' => 'Pareigybė', 'en' => 'Duty'],
        'institution_id' => $duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
        'types' => $types,
    ];
}

test('editing a type alone cannot grant its responsibilities to the actor', function (): void {
    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertForbidden();

    expect($this->actorDuty->fresh()->hasRole($this->privileged))->toBeFalse();
    expect($this->type->duties()->exists())->toBeFalse();
});

test('duty creation and editing refuse undelegated types before changing data', function (): void {
    $count = Duty::query()->count();
    asUser($this->actor)->post(route('duties.store'), dutyTypeAssignmentPayload($this->actorDuty, [$this->type->id]))->assertForbidden();
    expect(Duty::query()->count())->toBe($count);

    $name = $this->actorDuty->getTranslations('name');
    asUser($this->actor)->put(route('duties.update', $this->actorDuty), dutyTypeAssignmentPayload($this->actorDuty, [$this->type->id]))->assertForbidden();
    expect($this->actorDuty->fresh()->getTranslations('name'))->toBe($name);
    expect($this->actorDuty->hasRole($this->privileged))->toBeFalse();
});

test('delegation allows assigning an unheld responsibility through both editors', function (): void {
    $this->manager->attachable_types()->attach($this->type);
    $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    expect($this->actorDuty->hasRole($this->privileged))->toBeFalse();

    asUser($this->actor)->put(route('duties.update', $duty), dutyTypeAssignmentPayload($duty, [$this->type->id]))->assertSessionHasNoErrors();
    expect($duty->fresh()->hasRole($this->privileged))->toBeTrue();

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id, $duty->id]])->assertSessionHasNoErrors();
    expect($this->actorDuty->fresh()->hasRole($this->privileged))->toBeTrue();

    asUser($this->actor)->put(route('duties.update', $duty), dutyTypeAssignmentPayload($duty, []))->assertSessionHasNoErrors();
    expect($duty->fresh()->hasRole($this->privileged))->toBeFalse();
});

test('delegated types are available when creating duties', function (): void {
    $this->manager->givePermissionTo(['duties.read.padalinys', 'institutions.create.padalinys']);
    $this->manager->attachable_types()->attach($this->type);
    $blocked = DutyType::factory()->create();

    asUser($this->actor)->get(route('duties.create'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('dutyTypes', fn ($types) => collect($types)->pluck('id')->all() === [$this->type->id]));

    asUser($this->actor)->post(route('duties.store'), dutyTypeAssignmentPayload($this->actorDuty, [$this->type->id]))->assertSessionHasNoErrors();
    $created = Duty::query()->latest('id')->firstOrFail();
    expect($created->hasRole($this->privileged))->toBeTrue();
    expect(GetAttachableTypesForDuty::execute()->pluck('id')->all())->not->toContain($blocked->id);
});

test('an ended delegation cannot be used but an assignment ending today still can', function (): void {
    $delegation = Role::factory()->create();
    $delegation->attachable_types()->attach($this->type);
    $oldDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $oldDuty->assignRole($delegation);
    $this->actor->duties()->attach($oldDuty, ['start_date' => today()->subMonth(), 'end_date' => today()->subDay()]);

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertForbidden();
    expect(GetAttachableTypesForDuty::execute()->contains('id', $this->type->id))->toBeFalse();

    $this->actor->dutiables()->where('duty_id', $oldDuty->id)->firstOrFail()->update(['end_date' => today()]);
    asUser($this->actor->fresh())->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertSessionHasNoErrors();
});

test('a mixed batch cannot change a foreign duty and performs no partial writes', function (): void {
    $this->manager->attachable_types()->attach($this->type);
    $foreignTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $foreign = Duty::factory()->for(Institution::factory()->for($foreignTenant))->create();

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id, $foreign->id]])->assertForbidden();
    expect($this->type->duties()->count())->toBe(0);
});

test('a future duty does not delegate its types yet', function (): void {
    $delegation = Role::factory()->create();
    $delegation->attachable_types()->attach($this->type);
    $future = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $future->assignRole($delegation);
    $this->actor->duties()->attach($future, ['start_date' => today()->addDay(), 'end_date' => null]);

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertForbidden();
    expect($this->actorDuty->fresh()->hasRole($this->privileged))->toBeFalse();
});

test('even administrators cannot assign a legacy type that grants super administrator', function (): void {
    $superAdmin = Role::findByName(config('permission.super_admin_role_name'));
    ResponsibilityGrant::factory()->forDutyType($this->type)->of($superAdmin)->create();

    asUser(makeAdminUser($this->tenant))->put(route('duties.update', $this->actorDuty), dutyTypeAssignmentPayload($this->actorDuty, [$this->type->id]))->assertForbidden();
    expect($this->actorDuty->fresh()->hasRole($superAdmin))->toBeFalse();
});

test('foreign unchanged assignments stay while an authorised local assignment changes', function (): void {
    $this->manager->attachable_types()->attach($this->type);
    $foreignTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $foreign = Duty::factory()->for(Institution::factory()->for($foreignTenant))->create();
    $foreign->types()->attach($this->type);

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$foreign->id, $this->actorDuty->id]])->assertSessionHasNoErrors();
    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertForbidden();
    expect($this->type->duties()->count())->toBe(2);
});

test('undelegated unchanged types survive saves and cannot be removed', function (): void {
    $this->actorDuty->types()->attach($this->type);

    asUser($this->actor)->put(route('duties.update', $this->actorDuty), dutyTypeAssignmentPayload($this->actorDuty, [$this->type->id]))->assertSessionHasNoErrors();
    $withoutTypes = dutyTypeAssignmentPayload($this->actorDuty, []);
    unset($withoutTypes['types']);
    asUser($this->actor)->put(route('duties.update', $this->actorDuty), $withoutTypes)->assertSessionHasNoErrors();
    asUser($this->actor)->put(route('duties.update', $this->actorDuty), dutyTypeAssignmentPayload($this->actorDuty, []))->assertForbidden();
    expect($this->actorDuty->fresh()->hasRole($this->privileged))->toBeTrue();
});

test('responsibility managers can assign types but still need duty access', function (): void {
    $this->manager->givePermissionTo('roles.update.*');

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertSessionHasNoErrors();
    expect($this->actorDuty->fresh()->hasRole($this->privileged))->toBeTrue();
});

test('membership editing preserves soft-deleted duties', function (): void {
    $this->manager->attachable_types()->attach($this->type);
    $trashed = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $trashed->types()->attach($this->type);
    $trashed->delete();

    asUser($this->actor)->put(route('dutyTypes.models.sync', $this->type), ['models' => [$this->actorDuty->id]])->assertSessionHasNoErrors();
    expect($trashed->types()->whereKey($this->type->id)->exists())->toBeTrue();
});

test('the retired type grant endpoint cannot write scope-less grants', function (): void {
    expect(app('router')->has('dutyTypes.roles.sync'))->toBeFalse();
    asUser(makeAdminUser($this->tenant))->put('/mano/dutyTypes/'.$this->type->id.'/roles', ['roles' => [$this->privileged->id]])->assertMethodNotAllowed();
});

test('the responsibility editor requires a type grant scope and resolves its holders', function (ResponsibilityPurpose $purpose): void {
    $role = RoleFactory::forPurpose($purpose);
    $this->actorDuty->types()->attach($this->type);
    $admin = makeAdminUser($this->tenant);
    $payload = ['grantee_type' => 'duty_type', 'grantee_id' => (string) $this->type->id];

    asUser($admin)->post(route('responsibilities.grants.store', $role), $payload)->assertSessionHasErrors(['scope_type', 'scope_id']);
    expect($role->grants()->where('grantee_type', 'duty_type')->exists())->toBeFalse();

    asUser($admin)->post(route('responsibilities.grants.store', $role), [
        ...$payload, 'scope_type' => 'tenant', 'scope_id' => (string) $this->tenant->id,
    ])->assertSessionHasNoErrors();
    expect(app(ResponsibilityResolver::class)->dutiesForTenant($purpose, $this->tenant)->pluck('id')->all())->toContain($this->actorDuty->id);
})->with([ResponsibilityPurpose::StudentRepCoordination, ResponsibilityPurpose::MemberRegistration]);

test('the type record lists grants with scope warnings and hides unauthorised management', function (): void {
    $coordination = RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination);
    ResponsibilityGrant::factory()->forDutyType($this->type)->of($coordination)->create();

    asUser($this->actor)->get(route('dutyTypes.show', $this->type))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('can.manageAssignments', false)
            ->where('can.manageResponsibilities', false)->missing('roleOptions')
            ->where('responsibilityGrants', fn ($grants) => collect($grants)->contains(fn ($grant) => $grant['needs_scope'] && $grant['href'] === null)));
});
