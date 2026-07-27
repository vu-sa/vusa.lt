<?php

use App\Models\FieldResponse;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use App\Settings\FormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
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
    $this->managerRole = Role::factory()->create(['name' => 'Institution Manager']);

    $formSettings = app(FormSettings::class);
    $formSettings->member_registration_form_id = $this->memberForm->id;
    $formSettings->member_registration_notification_recipient_role_id = $this->recipientRole->id;
    $formSettings->student_rep_registration_form_id = $this->studentRepForm->id;
    $formSettings->save();

    $atstovavimasSettings = app(AtstovavimasSettings::class);
    $atstovavimasSettings->institution_manager_role_id = $this->managerRole->id;
    $atstovavimasSettings->save();
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

function createInstitutionRegistration(Form $form, FormField $field, Institution $institution): Registration
{
    $registration = Registration::factory()->for($form)->create();

    FieldResponse::factory()
        ->for($registration)
        ->for($field, 'formField')
        ->create(['response' => ['value' => $institution->id]]);

    return $registration;
}

describe('member registration form access', function () {
    test('the configured recipient role can view it through a duty', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(200);
    });

    test('the configured recipient role can view it when assigned directly', function () {
        $user = makeUser($this->tenant);
        $user->assignRole($this->recipientRole->name);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(200);
    });

    test('a user who can read forms for a tenant can still view it', function () {
        $user = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(200);
    });

    test('a plain authenticated user is now forbidden', function () {
        // Previously FormPolicy short-circuited to true for this form for anyone logged in.
        $user = makeUser($this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertStatus(403);
    });

    test('the configured recipient can open the forms index without form permissions', function () {
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

    test('a tenant form reader sees tenant forms and the shared member form', function () {
        $user = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
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
                        && ! $ids->contains($this->studentRepForm->id)
                        && $ids->count() === 2;
                })
            );
    });
});

describe('student rep registration form access', function () {
    test('the configured institution manager role can view it', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->managerRole);

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertStatus(200);
    });

    test('a plain authenticated user is forbidden', function () {
        $user = makeUser($this->tenant);

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertStatus(403);
    });

    test('the configured manager can open the forms index without form permissions', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->managerRole);
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

    test('shows registrations only for institutions in the managed tenant', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->managerRole);
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

    test('a directly assigned manager role is limited to the users tenants', function () {
        $user = makeUser($this->tenant);
        $user->assignRole($this->managerRole->name);
        AtstovavimasSettings::clearManagerCache($user->id);

        $managedInstitution = $user->current_duties()->first()->institution;
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        $visibleRegistration = createInstitutionRegistration(
            $this->studentRepForm,
            $this->studentInstitutionField,
            $managedInstitution,
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
                ->has('registrations', 1)
                ->where('registrations.0.id', $visibleRegistration->id)
            );
    });

    test('fails closed when the student representative form has no institution field', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->managerRole);
        $this->studentInstitutionField->delete();

        asUser($user)
            ->get(route('forms.show', $this->studentRepForm))
            ->assertForbidden();
    });

    test('a super administrator still sees registrations from every tenant', function () {
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

describe('shared registrationForms prop', function () {
    test('carries only the ids the user may open', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.registrationForms.member', $this->memberForm->id)
                ->where('auth.registrationForms.studentRep', null)
            );
    });

    test('carries both ids for an institution manager who also handles member registrations', function () {
        $user = makeUserWithDutyRole($this->tenant, $this->recipientRole);
        $user->duties()->first()->assignRole($this->managerRole->name);

        asUser($user)
            ->get(route('forms.show', $this->memberForm))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.registrationForms.member', $this->memberForm->id)
                ->where('auth.registrationForms.studentRep', $this->studentRepForm->id)
            );
    });

    test('is empty for a user with no relevant role', function () {
        $user = makeUser($this->tenant);

        asUser($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.registrationForms.member', null)
                ->where('auth.registrationForms.studentRep', null)
            );
    });
});
