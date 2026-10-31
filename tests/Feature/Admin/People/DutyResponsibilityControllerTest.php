<?php

use App\Enums\ResponsibilityPurpose;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use Database\Factories\RoleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $this->coordination = RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination);

    Role::factory()->create(['name' => 'Atsakomybių valdytojas'])
        ->givePermissionTo(['duties.update.padalinys', 'duties.read.padalinys', 'roles.update.*', 'roles.read.*']);
    $this->manager = makeTenantUserWithRole('Atsakomybių valdytojas', $this->tenant);
    $this->editor = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->member = makeUser($this->tenant);
    $this->duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
});

function coordinationFor(Role $role, Tenant $tenant): array
{
    return ['role_id' => $role->id, 'scope_type' => 'tenant', 'scope_id' => (string) $tenant->id];
}

test('a responsibility manager grants and removes a scoped responsibility with activity records', function (): void {
    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), coordinationFor($this->coordination, $this->tenant))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $grant = $this->duty->responsibilityGrants()->firstOrFail();

    expect($grant->scope_id)->toBe((string) $this->tenant->id)
        ->and($this->duty->roles()->pluck('roles.id')->all())->toContain($this->coordination->id);
    $this->assertDatabaseHas('activity_log', ['subject_type' => 'role', 'subject_id' => $this->coordination->id, 'causer_id' => $this->manager->id, 'event' => 'relation_updated']);

    $created = \App\Models\Activity::forSubject($this->coordination)->forEvent('relation_updated')->sole();
    expect($created->properties->get('grant'))->toBe($grant->only(['id', 'grantee_type', 'grantee_id', 'scope_type', 'scope_id']))
        ->and($created->properties->get('attached')[0]['id'])->toBe($grant->id);

    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), coordinationFor($this->coordination, $this->tenant))
        ->assertSessionHasNoErrors();
    expect(\App\Models\Activity::forSubject($this->coordination)->forEvent('relation_updated')->count())->toBe(1);

    asUser($this->manager)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $grant->id]))
        ->assertRedirect();

    $deleted = \App\Models\Activity::forSubject($this->coordination)->forEvent('relation_updated')->latest('id')->firstOrFail();
    expect($deleted->properties->get('detached')[0]['id'])->toBe($grant->id)
        ->and($deleted->properties->get('attached'))->toBe([])
        ->and(\App\Models\Activity::forSubject($this->coordination)->forEvent('relation_updated')->count())->toBe(2);

    expect($this->duty->responsibilityGrants()->exists())->toBeFalse()
        ->and($this->duty->roles()->exists())->toBeFalse();
    $this->assertDatabaseHas('activity_log', ['subject_type' => 'role', 'subject_id' => $this->coordination->id, 'causer_id' => $this->manager->id, 'event' => 'relation_updated']);
});

test('a duty editor may pass on a responsibility they hold, but nothing beyond it', function (): void {
    $theirs = Role::query()->where('name', 'Komunikacijos koordinatorius')->firstOrFail();
    $wider = Role::factory()->create();
    $widerGrant = ResponsibilityGrant::factory()->forDuty($this->duty)->of($wider)->create();

    asUser($this->editor)
        ->post(route('duties.responsibilities.store', $this->duty), ['role_id' => $theirs->id])
        ->assertSessionHasNoErrors();

    asUser($this->editor)
        ->post(route('duties.responsibilities.store', $this->duty), ['role_id' => $this->coordination->id, 'scope_type' => 'tenant', 'scope_id' => (string) $this->tenant->id])
        ->assertSessionHasErrors('role_id');

    asUser($this->editor)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $widerGrant->id]))
        ->assertForbidden();

    expect($this->duty->responsibilityGrants()->pluck('role_id')->all())->toEqualCanonicalizing([$theirs->id, $wider->id]);
});

test('the super admin role can never be granted to a duty', function (): void {
    $superAdmin = Role::findByName(config('permission.super_admin_role_name'));

    asUser(makeAdminUser($this->tenant))
        ->post(route('duties.responsibilities.store', $this->duty), ['role_id' => $superAdmin->id])
        ->assertSessionHasErrors('role_id');
});

test('a member without duty update permission cannot add or remove responsibilities', function (): void {
    $grant = ResponsibilityGrant::factory()->forDuty($this->duty)->forTenant($this->tenant)->create();

    asUser($this->member)
        ->post(route('duties.responsibilities.store', $this->duty), coordinationFor($this->coordination, $this->tenant))
        ->assertForbidden();

    asUser($this->member)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $grant->id]))
        ->assertForbidden();

    expect($grant->fresh())->not->toBeNull();
});

test('a scope must fit the purpose and stay within the manager\'s padaliniai', function (): void {
    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), coordinationFor($this->coordination, $this->otherTenant))
        ->assertSessionHasErrors('scope_id');

    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), [...coordinationFor($this->coordination, $this->tenant), 'scope_type' => 'role'])
        ->assertSessionHasErrors('scope_type');

    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), coordinationFor(Role::factory()->create(), $this->tenant))
        ->assertSessionHasErrors(['scope_type', 'scope_id']);

    expect($this->duty->responsibilityGrants()->exists())->toBeFalse();
});

test('a grant id under another duty returns 404', function (): void {
    $otherDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $grant = ResponsibilityGrant::factory()->forDuty($otherDuty)->forTenant($this->tenant)->create();

    asUser($this->manager)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $grant->id]))
        ->assertNotFound();

    expect($grant->fresh())->not->toBeNull();
});

test('the duty page defers its responsibilities, labelling those it gets through its type', function (): void {
    $type = DutyType::factory()->create();
    $this->duty->types()->attach($type);
    $typeRole = Role::factory()->create();
    ResponsibilityGrant::factory()->forDutyType($type)->of($typeRole)->create();
    ResponsibilityGrant::factory()->forDuty($this->duty)->forTenant($this->tenant)->create();

    asUserWithInertia($this->manager)
        ->get(route('duties.show', $this->duty))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('responsibilities')
            ->missing('responsibilityOptions')
            ->loadDeferredProps('dutyPanels', fn (Assert $deferred) => $deferred
                ->has('responsibilities.items', 2)
                ->where('responsibilities.items.0.purpose', 'student_rep_coordination')
                ->where('responsibilities.items.1.source', 'duty_type')
                ->where('responsibilities.items.1.role_id', $typeRole->id)));

    asUser($this->member)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Admin/People/ShowDuty',
            'X-Inertia-Partial-Data' => 'responsibilityOptions',
        ])
        ->get(route('duties.show', $this->duty))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('duty responsibility descriptions use the current locale with Lithuanian fallback', function (): void {
    $role = Role::factory()->create(['description' => ['lt' => 'Padėk atstovams.', 'en' => 'Help representatives.']]);
    ResponsibilityGrant::factory()->forDuty($this->duty)->of($role)->create();

    app()->setLocale('en');
    $payload = \App\Http\Controllers\Admin\DutyResponsibilityController::payload($this->duty);
    expect($payload['items'][0]['description'])->toBe('Help representatives.');

    $role->setTranslations('description', ['lt' => 'Padėk atstovams.', 'en' => ''])->save();
    $payload = \App\Http\Controllers\Admin\DutyResponsibilityController::payload($this->duty->fresh());
    expect($payload['items'][0]['description'])->toBe('Padėk atstovams.');

    $role->setTranslations('description', ['lt' => '', 'en' => ''])->save();
    $payload = \App\Http\Controllers\Admin\DutyResponsibilityController::payload($this->duty->fresh());
    expect($payload['items'][0]['description'])->toBeNull();
});
