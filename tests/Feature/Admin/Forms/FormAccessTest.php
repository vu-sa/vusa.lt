<?php

use App\Models\DutyResponsibility;
use App\Models\FieldResponse;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Settings\FormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->centralTenant = Tenant::query()->first();
    $this->tenant = Tenant::query()
        ->whereKeyNot($this->centralTenant->id)
        ->first() ?? Tenant::factory()->create(['type' => 'padalinys']);

    // Both special forms belong to the central tenant, which is what makes the
    // ordinary tenant check fail for everyone else.
    $this->memberForm = Form::factory()->for($this->centralTenant)->create([
        'name' => ['lt' => 'Narių registracija', 'en' => 'Member registration'],
    ]);
    $this->studentRepForm = Form::factory()->for($this->centralTenant)->create([
        'name' => ['lt' => 'Atstovų registracija', 'en' => 'Student rep registration'],
    ]);
    $this->memberTenantField = FormField::factory()->for($this->memberForm)->create([
        'type' => 'enum',
        'use_model_options' => true,
        'options_model' => Tenant::class,
    ]);
    $this->studentInstitutionField = FormField::factory()->for($this->studentRepForm)->create([
        'type' => 'enum',
        'use_model_options' => true,
        'options_model' => Institution::class,
    ]);

    $this->recipientRole = Role::factory()->create(['name' => 'Member Registration Recipient']);

    $formSettings = app(FormSettings::class);
    $formSettings->member_registration_form_id = $this->memberForm->id;
    $formSettings->member_registration_notification_recipient_role_id = $this->recipientRole->id;
    $formSettings->student_rep_registration_form_id = $this->studentRepForm->id;
    $formSettings->save();
});

/**
 * A user whose duty carries the given role — the way roles are actually assigned in this app.
 */
function makeUserWithDutyRole(Tenant $tenant, Role $role): User
{
    $user = makeUser($tenant);
    $duty = $user->duties()->first();
    $duty->pivot->end_date = null;
    $duty->pivot->save();
    $duty->assignRole($role->name);

    return $user;
}

/** A user whose current duty coordinates the padalinys' student representatives. */
function makeCoordinator(Tenant $tenant): User
{
    $user = makeUser($tenant);
    $duty = $user->duties()->first();
    $duty->pivot->end_date = null;
    $duty->pivot->save();
    DutyResponsibility::factory()->for($duty)->forTenant($tenant)->create();

    return $user;
}

function createInstitutionRegistration(Form $form, FormField $field, Institution $institution): Registration
{
    $registration = Registration::factory()->for($form)->create();

    FieldResponse::factory()
        ->for($registration)
        ->for($field, 'formField')
        ->create(['response' => ['value' => $institution->id]]);

    return $registration;
}

describe('member registration form access', function (): void {
    test('the configured recipient role can view it through a duty', function (): void {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(200);
    });

    test('the configured recipient role can view it when assigned directly', function (): void {
        $user = makeUser($this->tenant);
        $user->assignRole($this->recipientRole->name);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(200);
    });

    test('a user who can read forms for a tenant can still view it', function (): void {
        $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(200);
    });

    test('a plain authenticated user is now forbidden', function (): void {
        // Previously FormPolicy short-circuited to true for this form for anyone logged in.
        $user = makeUser($this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(403);
    });

    test('the configured recipient can open the forms index without form permissions', function (): void {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);
        Form::factory()->for($this->centralTenant)->create();
        Form::factory()->for($this->tenant)->create();

        asUser($user)
            ->get(route('forms.index'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Forms/IndexForm')
                ->where('auth.can.index.form', true)
                ->where('can.create', false)
                ->has('forms.data', 1)
                ->where('forms.data.0.id', $this->memberForm->id)
                ->where('forms.data.0.can.view', true)
                ->where('forms.data.0.can.update', false)
                ->where('forms.data.0.can.delete', false)
            );
    });

    test('a tenant form reader sees tenant forms and both shared registration forms', function (): void {
        $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $tenantForm = Form::factory()->for($this->tenant)->create();
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        Form::factory()->for($otherTenant)->create();

        asUser($user)
            ->get(route('forms.index'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('forms.data', function ($forms) use ($tenantForm) {
                    $ids = collect($forms)->pluck('id');

                    return $ids->contains($tenantForm->id)
                        && $ids->contains($this->memberForm->id)
                        && $ids->contains($this->studentRepForm->id)
                        && $ids->count() === 3;
                })
            );
    });
});

describe('student rep registration form access', function (): void {
    test('a coordinator of the padalinys can view it', function (): void {
        $user = makeCoordinator($this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertStatus(200);
    });

    test('a plain authenticated user is forbidden', function (): void {
        $user = makeUser($this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertStatus(403);
    });

    test('a coordinator can open the forms index without form permissions', function (): void {
        $user = makeCoordinator($this->tenant);
        Form::factory()->for($this->centralTenant)->create();
        Form::factory()->for($this->tenant)->create();

        asUser($user)
            ->get(route('forms.index'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->has('forms.data', 1)
                ->where('forms.data.0.id', $this->studentRepForm->id)
                ->where('forms.data.0.can.update', false)
                ->where('forms.data.0.can.delete', false)
            );
    });

    test('shows registrations only for institutions in the managed tenant', function (): void {
        $user = makeCoordinator($this->tenant);
        $managedInstitution = $user->current_duties()->first()->institution;
        $sameTenantInstitution = Institution::factory()->for($this->tenant)->create();
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        $managedRegistration = createInstitutionRegistration(
            $this->studentRepForm,
            $this->studentInstitutionField,
            $managedInstitution,
        );
        $sameTenantRegistration = createInstitutionRegistration(
            $this->studentRepForm,
            $this->studentInstitutionField,
            $sameTenantInstitution,
        );
        createInstitutionRegistration(
            $this->studentRepForm,
            $this->studentInstitutionField,
            $otherInstitution,
        );

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('registrations', function ($registrations) use ($managedRegistration, $sameTenantRegistration) {
                    $ids = collect($registrations)->pluck('id');

                    return $ids->contains($managedRegistration->id)
                        && $ids->contains($sameTenantRegistration->id)
                        && $ids->count() === 2;
                })
                ->where('institutions', function ($institutions) use ($managedInstitution, $sameTenantInstitution) {
                    $ids = collect($institutions)->pluck('id');

                    return $ids->contains($managedInstitution->id)
                        && $ids->contains($sameTenantInstitution->id)
                        && $ids->count() === 2;
                })
                ->where('can.update', false)
                ->where('can.export', false)
                ->where('exportUrl', null)
            );

        asUser($user)
            ->get(route('forms.index'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('forms.data.0.registrations_count', 2)
            );
    });

    test('leaves out an institution whose type another duty coordinates', function (): void {
        $user = makeCoordinator($this->tenant);
        $ownInstitution = Institution::factory()->for($this->tenant)->create();
        $senate = Institution::factory()->for($this->tenant)->create();
        $senateType = Type::factory()->forInstitutions()->create();
        $senate->types()->attach($senateType);
        DutyResponsibility::factory()->forType($senateType)->create();

        $visible = createInstitutionRegistration($this->studentRepForm, $this->studentInstitutionField, $ownInstitution);
        createInstitutionRegistration($this->studentRepForm, $this->studentInstitutionField, $senate);

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertInertia(fn (Assert $page) => $page
                ->has('registrations', 1)
                ->where('registrations.0.id', $visible->id)
            );
    });

    test('fails closed when the student representative form has no institution field', function (): void {
        $user = makeCoordinator($this->tenant);
        $this->studentInstitutionField->delete();

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertForbidden();
    });

    test('a super administrator still sees registrations from every tenant', function (): void {
        $managedInstitution = Institution::factory()->for($this->tenant)->create();
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        createInstitutionRegistration(
            $this->studentRepForm,
            $this->studentInstitutionField,
            $managedInstitution,
        );
        createInstitutionRegistration(
            $this->studentRepForm,
            $this->studentInstitutionField,
            $otherInstitution,
        );

        asUser(makeAdminUser())
            ->get(route('forms.show', $this->studentRepForm))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->has('registrations', 2)
                ->where('can.update', true)
                ->where('can.export', true)
            );
    });
});

describe('catalog registration sections', function (): void {
    test('carries only the forms the user may open', function (): void {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminNavigation.workspaces', function ($workspaces): bool {
                    $organization = collect($workspaces)->firstWhere('key', 'organizacija');
                    if (! $organization) {
                        return false;
                    }

                    $sectionKeys = collect($organization['sections'])->pluck('key');

                    return $sectionKeys->contains('registracija_nariai') && ! $sectionKeys->contains('registracija_atstovai');
                })
            );
    });

    test('carries both forms for a coordinator who also handles member registrations', function (): void {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);
        DutyResponsibility::factory()->for($user->duties()->first())->forTenant($this->tenant)->create();

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminNavigation.workspaces', function ($workspaces): bool {
                    $organization = collect($workspaces)->firstWhere('key', 'organizacija');
                    if (! $organization) {
                        return false;
                    }

                    $sectionKeys = collect($organization['sections'])->pluck('key');

                    return $sectionKeys->contains('registracija_nariai') && $sectionKeys->contains('registracija_atstovai');
                })
            );
    });

    test('is empty for a user with no relevant role', function (): void {
        $user = makeUser($this->tenant);

        asUser($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('adminNavigation.workspaces', function ($workspaces): bool {
                    $organization = collect($workspaces)->firstWhere('key', 'organizacija');
                    if (! $organization) {
                        return true;
                    }

                    $sectionKeys = collect($organization['sections'])->pluck('key');

                    return ! $sectionKeys->contains('registracija_nariai') && ! $sectionKeys->contains('registracija_atstovai');
                })
            );
    });
});
