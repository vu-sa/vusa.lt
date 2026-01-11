<?php

use App\Events\StudentRepRegistrationCreated;
use App\Mail\ConfirmStudentRepRegistration;
use App\Models\Duty;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\StudentRepRegistered;
use App\Settings\FormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

describe('StudentRepRegistrationCreated event dispatch', function () {
    test('event is dispatched when student rep form is submitted', function () {
        Event::fake([StudentRepRegistrationCreated::class]);

        $form = createStudentRepForm($this->tenant, $this->institution);

        // Configure the form in settings
        $settings = app(FormSettings::class);
        $settings->student_rep_registration_form_id = $form->id;
        $settings->save();

        // Get form fields for submission
        $fields = $form->formFields;
        $nameField = $fields->firstWhere('subtype', 'name');
        $emailField = $fields->firstWhere('subtype', 'email');
        $institutionField = $fields->first(fn ($f) => $f->use_model_options && $f->options_model === Institution::class);

        $response = asUser($this->user)->post(route('registrations.store', $form), [
            'data' => [
                $nameField->id => ['value' => 'Jonas Jonaitis'],
                $emailField->id => ['value' => 'jonas@example.com'],
                $institutionField->id => ['value' => $this->institution->id],
            ],
        ]);

        $response->assertRedirect();

        Event::assertDispatched(StudentRepRegistrationCreated::class, function ($event) {
            return $event->institution->id === $this->institution->id;
        });
    });

    test('event is not dispatched for non-student-rep forms', function () {
        Event::fake([StudentRepRegistrationCreated::class]);

        // Create a regular form (not set as student rep form in settings)
        $form = Form::factory()->published()->create(['tenant_id' => $this->tenant->id]);

        $textField = FormField::factory()->create([
            'form_id' => $form->id,
            'type' => 'string',
            'subtype' => 'name',
            'is_required' => true,
        ]);

        asUser($this->user)->post(route('registrations.store', $form), [
            'data' => [
                $textField->id => ['value' => 'Test Value'],
            ],
        ]);

        Event::assertNotDispatched(StudentRepRegistrationCreated::class);
    });
});

describe('SendStudentRepRegistrationNotification listener', function () {
    test('confirmation email is sent to registrant', function () {
        Mail::fake();
        Notification::fake();

        $form = createStudentRepForm($this->tenant, $this->institution);

        $settings = app(FormSettings::class);
        $settings->student_rep_registration_form_id = $form->id;
        $settings->save();

        // Create a manager for the institution
        createInstitutionManager($this->tenant, $this->institution);

        $fields = $form->formFields;
        $nameField = $fields->firstWhere('subtype', 'name');
        $emailField = $fields->firstWhere('subtype', 'email');
        $institutionField = $fields->first(fn ($f) => $f->use_model_options);

        asUser($this->user)->post(route('registrations.store', $form), [
            'data' => [
                $nameField->id => ['value' => 'Jonas Jonaitis'],
                $emailField->id => ['value' => 'jonas@example.com'],
                $institutionField->id => ['value' => $this->institution->id],
            ],
        ]);

        Mail::assertSent(ConfirmStudentRepRegistration::class, function ($mail) {
            return $mail->hasTo('jonas@example.com');
        });
    });

    test('notification is sent to institution managers', function () {
        Mail::fake();
        Notification::fake();

        $form = createStudentRepForm($this->tenant, $this->institution);

        $settings = app(FormSettings::class);
        $settings->student_rep_registration_form_id = $form->id;
        $settings->save();

        $manager = createInstitutionManager($this->tenant, $this->institution);

        $fields = $form->formFields;
        $nameField = $fields->firstWhere('subtype', 'name');
        $emailField = $fields->firstWhere('subtype', 'email');
        $institutionField = $fields->first(fn ($f) => $f->use_model_options);

        asUser($this->user)->post(route('registrations.store', $form), [
            'data' => [
                $nameField->id => ['value' => 'Jonas Jonaitis'],
                $emailField->id => ['value' => 'jonas@example.com'],
                $institutionField->id => ['value' => $this->institution->id],
            ],
        ]);

        Notification::assertSentTo($manager, StudentRepRegistered::class);
    });
});

describe('registration is stored correctly', function () {
    test('registration and field responses are saved', function () {
        Event::fake([StudentRepRegistrationCreated::class]);

        $form = createStudentRepForm($this->tenant, $this->institution);

        $settings = app(FormSettings::class);
        $settings->student_rep_registration_form_id = $form->id;
        $settings->save();

        $fields = $form->formFields;
        $nameField = $fields->firstWhere('subtype', 'name');
        $emailField = $fields->firstWhere('subtype', 'email');
        $institutionField = $fields->first(fn ($f) => $f->use_model_options);

        asUser($this->user)->post(route('registrations.store', $form), [
            'data' => [
                $nameField->id => ['value' => 'Jonas Jonaitis'],
                $emailField->id => ['value' => 'jonas@example.com'],
                $institutionField->id => ['value' => $this->institution->id],
            ],
        ]);

        $this->assertDatabaseHas('registrations', [
            'form_id' => $form->id,
            'user_id' => $this->user->id,
        ]);

        $registration = Registration::where('form_id', $form->id)->first();

        $this->assertDatabaseHas('field_responses', [
            'registration_id' => $registration->id,
            'form_field_id' => $nameField->id,
        ]);

        $this->assertDatabaseHas('field_responses', [
            'registration_id' => $registration->id,
            'form_field_id' => $emailField->id,
        ]);
    });
});

describe('FormSettings for student rep registration', function () {
    test('student rep form ID can be set and retrieved', function () {
        $form = Form::factory()->published()->create(['tenant_id' => $this->tenant->id]);

        $settings = app(FormSettings::class);
        $settings->student_rep_registration_form_id = $form->id;
        $settings->save();

        // Refresh settings
        $freshSettings = app(FormSettings::class);
        expect($freshSettings->student_rep_registration_form_id)->toBe($form->id);
    });

    test('student rep institution type IDs can be set and retrieved', function () {
        $settings = app(FormSettings::class);
        $settings->setStudentRepInstitutionTypeIds([1, 2, 3]);
        $settings->save();

        $freshSettings = app(FormSettings::class);
        expect($freshSettings->getStudentRepInstitutionTypeIds()->toArray())->toBe([1, 2, 3]);
    });
});

// Helper function to create a student rep registration form
function createStudentRepForm(Tenant $tenant, Institution $institution): Form
{
    $form = Form::factory()->published()->create(['tenant_id' => $tenant->id]);

    FormField::factory()->create([
        'form_id' => $form->id,
        'type' => 'string',
        'subtype' => 'name',
        'label' => ['lt' => 'Vardas, pavardė', 'en' => 'Name'],
        'is_required' => true,
        'order' => 1,
    ]);

    FormField::factory()->create([
        'form_id' => $form->id,
        'type' => 'string',
        'subtype' => 'email',
        'label' => ['lt' => 'El. paštas', 'en' => 'Email'],
        'is_required' => true,
        'order' => 2,
    ]);

    FormField::factory()->create([
        'form_id' => $form->id,
        'type' => 'enum',
        'label' => ['lt' => 'Institucija', 'en' => 'Institution'],
        'is_required' => true,
        'order' => 3,
        'use_model_options' => true,
        'options_model' => Institution::class,
    ]);

    return $form->fresh(['formFields']);
}

// Helper function to create an institution manager
function createInstitutionManager(Tenant $tenant, Institution $institution): User
{
    $permissionName = config('permission.institution_managership_indicating_permission');

    // Ensure permission exists
    $permission = Permission::firstOrCreate(
        ['name' => $permissionName, 'guard_name' => 'web']
    );

    // Create role with the permission
    $role = Role::firstOrCreate(
        ['name' => 'Institution Manager Test', 'guard_name' => 'web']
    );
    $role->givePermissionTo($permission);

    // Create user with duty in the institution
    $user = User::factory()->create();

    $duty = Duty::factory()
        ->for($institution)
        ->hasAttached($user, ['start_date' => now()->subDay(), 'end_date' => now()->addDay()])
        ->create();

    $duty->assignRole($role);

    return $user;
}
