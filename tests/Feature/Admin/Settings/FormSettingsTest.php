<?php

use App\Models\Form;
use App\Models\InstitutionType;
use App\Models\Tenant;
use App\Settings\FormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    app()->forgetInstance(FormSettings::class);
});

test('a settings manager can configure and deactivate both registration forms', function (): void {
    $memberForm = Form::factory()->published()->create(['tenant_id' => $this->tenant->id]);
    $repForm = Form::factory()->published()->create(['tenant_id' => $this->tenant->id]);
    $institutionType = InstitutionType::query()->firstOrFail();

    asUser($this->admin)->post(route('settings.forms.update'), [
        'member_registration_form_id' => $memberForm->id,
        'student_rep_registration_form_id' => $repForm->id,
        'student_rep_institution_type_ids' => [$institutionType->id],
    ])->assertRedirect()->assertSessionHasNoErrors();

    app()->forgetInstance(FormSettings::class);
    $settings = app(FormSettings::class);

    expect($settings->member_registration_form_id)->toBe($memberForm->id)
        ->and($settings->student_rep_registration_form_id)->toBe($repForm->id)
        ->and($settings->getStudentRepInstitutionTypeIds()->all())->toBe([$institutionType->id]);

    asUser($this->admin)->post(route('settings.forms.update'), [
        'member_registration_form_id' => null,
        'student_rep_registration_form_id' => null,
        'student_rep_institution_type_ids' => [],
    ])->assertRedirect()->assertSessionHasNoErrors();

    app()->forgetInstance(FormSettings::class);
    $settings = app(FormSettings::class);

    expect($settings->member_registration_form_id)->toBeNull()
        ->and($settings->student_rep_registration_form_id)->toBeNull()
        ->and($settings->getStudentRepInstitutionTypeIds()->all())->toBe([]);
});

test('form settings reject soft-deleted or unknown forms', function (): void {
    $deletedForm = Form::factory()->published()->create(['tenant_id' => $this->tenant->id]);
    $deletedForm->delete();

    asUser($this->admin)->post(route('settings.forms.update'), [
        'member_registration_form_id' => $deletedForm->id,
        'student_rep_registration_form_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    ])->assertSessionHasErrors(['member_registration_form_id', 'student_rep_registration_form_id']);
});

test('form settings require settings permissions to view or update', function (): void {
    asUser($this->user)->get(route('settings.forms.edit'))->assertForbidden();
    asUser($this->user)->post(route('settings.forms.update'), [
        'member_registration_form_id' => null,
        'student_rep_registration_form_id' => null,
    ])->assertForbidden();

    asUserWithInertia($this->admin)->get(route('settings.forms.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/EditFormSettings')
            ->has('member_registration_form_id')
            ->has('student_rep_registration_form_id')
        );
});
