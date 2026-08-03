<?php

use App\Models\FieldResponse;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Registration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

describe('unauthorized access', function (): void {
    test('cannot access index page', function (): void {
        asUser($this->user)
            ->get(route('forms.index'))
            ->assertStatus(403);
    });

    test('cannot view form', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->user)
            ->get(route('forms.show', $form))
            ->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)
            ->get(route('forms.create'))
            ->assertStatus(403);
    });

    test('cannot store form', function (): void {
        $data = [
            'name' => ['lt' => 'Test forma', 'en' => 'Test Form'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'path' => ['test-path'],
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->user)
            ->post(route('forms.store'), $data)
            ->assertStatus(403);
    });

    test('cannot access edit page', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->user)
            ->get(route('forms.edit', $form))
            ->assertStatus(403);
    });

    test('cannot update form', function (): void {
        $form = Form::factory()->for($this->tenant)->create();
        $data = [
            'name' => ['lt' => 'Updated forma', 'en' => 'Updated Form'],
            'description' => ['lt' => 'Updated aprašymas', 'en' => 'Updated description'],
            'path' => ['updated-path'],
            'tenant_id' => $this->tenant->id,
        ];

        asUser($this->user)
            ->put(route('forms.update', $form), $data)
            ->assertStatus(403);
    });

    test('cannot delete form', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->user)
            ->delete(route('forms.destroy', $form))
            ->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index page', function (): void {
        // Clear any existing forms to ensure clean test
        Form::query()->delete();

        $forms = Form::factory()->count(3)->for($this->tenant)->create();

        asUser($this->admin)
            ->get(route('forms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Forms/IndexForm')
                ->has('forms')
                ->has('forms.data', 3)
            );
    });

    test('index shows forms from all tenants for super admin', function (): void {
        // Clear any existing forms to ensure clean test
        Form::query()->delete();

        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        Form::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'My Form', 'en' => 'My Form'],
            'publish_time' => now()->addDay(),
        ]);
        Form::factory()->for($otherTenant)->create([
            'name' => ['lt' => 'Other Form', 'en' => 'Other Form'],
            'publish_time' => now(),
        ]);

        // Use Super Admin user for cross-tenant access
        $superAdmin = makeAdminUser();

        asUser($superAdmin)
            ->get(route('forms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('forms.data', 2) // Super Admin sees all forms
                ->where('forms.data.0.name.lt', 'My Form')
                ->where('forms.data.1.name.lt', 'Other Form')
            );
    });

    test('index supports search filtering', function (): void {
        // Clear any existing forms to ensure clean test
        Form::query()->delete();

        Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Important Form', 'en' => 'Important Form']]);
        Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Regular Form', 'en' => 'Regular Form']]);

        asUser($this->admin)
            ->get(route('forms.index', ['search' => 'Important']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('forms.data', 1)
                ->where('forms.data.0.name.lt', 'Important Form')
            );
    });

    test('can view form', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->admin)
            ->get(route('forms.show', $form))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Forms/ShowForm')
                ->has('form')
                ->where('form.id', $form->id)
            );
    });

    test('can view form from different tenant as super admin', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $form = Form::factory()->for($otherTenant)->create();

        $superAdmin = makeAdminUser();

        asUser($superAdmin)
            ->get(route('forms.show', $form))
            ->assertStatus(200); // Super Admin can access any tenant's forms
    });

    test('can access create page', function (): void {
        asUser($this->admin)
            ->get(route('forms.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Forms/CreateForm')
            );
    });

    test('can access edit page', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->admin)
            ->get(route('forms.edit', $form))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Forms/EditForm')
                ->has('form')
                ->where('form.id', $form->id)
            );
    });

    test('can edit form from different tenant as super admin', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $form = Form::factory()->for($otherTenant)->create();

        $superAdmin = makeAdminUser();

        asUser($superAdmin)
            ->get(route('forms.edit', $form))
            ->assertStatus(200); // Super Admin can access any tenant's forms
    });

    test('can delete form', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->admin)
            ->delete(route('forms.destroy', $form))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('forms', [
            'id' => $form->id,
        ]);
    });
});

describe('form creation with fields', function (): void {
    test('can create form with form fields', function (): void {
        $formData = [
            'name' => ['lt' => 'Registracijos forma', 'en' => 'Registration Form'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'path' => ['lt' => 'registracija', 'en' => 'registration'],
            'tenant_id' => $this->tenant->id,
            'publish_time' => now()->addHour()->toISOString(),
            'form_fields' => [
                [
                    'type' => 'text',
                    'label' => ['lt' => 'Pilnas vardas', 'en' => 'Full Name'],
                    'is_required' => true,
                    'order' => 1,
                ],
                [
                    'type' => 'email',
                    'label' => ['lt' => 'El. paštas', 'en' => 'Email'],
                    'is_required' => true,
                    'order' => 2,
                ],
                [
                    'type' => 'select',
                    'label' => ['lt' => 'Padalinys', 'en' => 'Department'],
                    'options' => ['option1', 'option2', 'option3'],
                    'is_required' => false,
                    'order' => 3,
                ],
            ],
        ];

        asUser($this->admin)
            ->post(route('forms.store'), $formData)
            ->assertRedirect()
            ->assertSessionHas('success');

        $form = Form::with('formFields')->where('tenant_id', $this->tenant->id)->latest()->first();

        expect($form)->not()->toBeNull()
            ->and($form->tenant_id)->toBe($this->tenant->id)
            ->and($form->formFields)->toHaveCount(3);

        $textField = $form->formFields->where('type', 'text')->first();
        expect($textField->is_required)->toBeTrue()
            ->and($textField->getTranslation('label', 'lt'))->toBe('Pilnas vardas');
    });

    test('can export form responses', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        // Create form field and responses for testing
        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'label' => ['lt' => 'Vardas', 'en' => 'Name'],
        ]);

        $registration = Registration::factory()->create([
            'form_id' => $form->id,
        ]);

        FieldResponse::factory()->create([
            'registration_id' => $registration->id,
            'form_field_id' => $field->id,
            'response' => 'Test Response',
        ]);

        asUser($this->admin)
            ->get(route('forms.export', $form))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});

describe('index sorting and scoping', function (): void {
    test('defaults to most recently updated first', function (): void {
        Form::query()->delete();

        $stale = Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Stale', 'en' => 'Stale']]);
        $fresh = Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Fresh', 'en' => 'Fresh']]);

        $stale->forceFill(['updated_at' => now()->subWeek()])->saveQuietly();
        $fresh->forceFill(['updated_at' => now()])->saveQuietly();

        asUser($this->admin)
            ->get(route('forms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('forms.data.0.name.lt', 'Fresh')
                ->where('forms.data.1.name.lt', 'Stale')
                ->where('sorting.0.id', 'updated_at')
                ->where('sorting.0.desc', true)
            );
    });

    test('does not list forms belonging to other tenants', function (): void {
        Form::query()->delete();

        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

        Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Mine', 'en' => 'Mine']]);
        Form::factory()->for($otherTenant)->create(['name' => ['lt' => 'Theirs', 'en' => 'Theirs']]);

        asUser($this->admin)
            ->get(route('forms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('forms.data', 1)
                ->where('forms.data.0.name.lt', 'Mine')
            );
    });

    test('exposes the registration count and no longer passes the shortcut card props', function (): void {
        Form::query()->delete();

        $form = Form::factory()->for($this->tenant)->create();
        $field = FormField::factory()->for($form)->create();

        $registration = Registration::factory()->for($form)->create();
        FieldResponse::factory()->for($registration)->for($field, 'formField')->create();

        asUser($this->admin)
            ->get(route('forms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('forms.data.0.registrations_count', 1)
                ->missing('memberFormId')
                ->missing('canAccessMemberForm')
                ->missing('studentRepFormId')
                ->missing('canAccessStudentRepForm')
            );
    });
});

describe('updating form fields', function (): void {
    test('cannot update a form field belonging to a different form', function (): void {
        $form = Form::factory()->for($this->tenant)->create();
        $otherForm = Form::factory()->for($this->tenant)->create();
        $foreignField = FormField::factory()->for($otherForm)->create([
            'label' => ['lt' => 'Svetimas', 'en' => 'Foreign'],
        ]);

        asUser($this->admin)
            ->put(route('forms.update', $form), [
                'name' => ['lt' => 'Forma', 'en' => 'Form'],
                'description' => ['lt' => '', 'en' => ''],
                'path' => ['lt' => 'forma', 'en' => 'form'],
                'tenant_id' => $this->tenant->id,
                'form_fields' => [
                    [
                        'id' => $foreignField->id,
                        'type' => 'string',
                        'label' => ['lt' => 'Pavogta', 'en' => 'Hijacked'],
                        'is_required' => false,
                        'order' => 1,
                        'options' => null,
                    ],
                ],
            ])
            ->assertStatus(403);

        expect($foreignField->fresh()->getTranslation('label', 'lt'))->toBe('Svetimas');
    });

    test('keeps the field description when the form already has registrations', function (): void {
        $form = Form::factory()->for($this->tenant)->create();
        $field = FormField::factory()->for($form)->create([
            'type' => 'string',
            'label' => ['lt' => 'Vardas', 'en' => 'Name'],
            'description' => ['lt' => 'Įrašykite vardą', 'en' => 'Enter your name'],
        ]);

        $registration = Registration::factory()->for($form)->create();
        FieldResponse::factory()->for($registration)->for($field, 'formField')->create();

        asUser($this->admin)
            ->put(route('forms.update', $form), [
                'name' => ['lt' => 'Forma', 'en' => 'Form'],
                'description' => ['lt' => '', 'en' => ''],
                'path' => ['lt' => 'forma', 'en' => 'form'],
                'tenant_id' => $this->tenant->id,
                'form_fields' => [
                    [
                        'id' => $field->id,
                        'type' => 'string',
                        'label' => ['lt' => 'Vardas', 'en' => 'Name'],
                        'description' => ['lt' => 'Įrašykite vardą', 'en' => 'Enter your name'],
                        'is_required' => false,
                        'order' => 1,
                        'options' => null,
                    ],
                ],
            ])
            ->assertRedirect();

        expect($field->fresh()->getTranslation('description', 'lt'))->toBe('Įrašykite vardą');
    });

    test('creates fields whose id carries the new- prefix', function (): void {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->admin)
            ->put(route('forms.update', $form), [
                'name' => ['lt' => 'Forma', 'en' => 'Form'],
                'description' => ['lt' => '', 'en' => ''],
                'path' => ['lt' => 'forma', 'en' => 'form'],
                'tenant_id' => $this->tenant->id,
                'form_fields' => [
                    [
                        'id' => 'new-6a4e2c1f-0b8d-4c2e-9f11-3a5b7c9d1e2f',
                        'type' => 'string',
                        'label' => ['lt' => 'Naujas', 'en' => 'New'],
                        'is_required' => false,
                        'order' => 1,
                        'options' => null,
                    ],
                ],
            ])
            ->assertRedirect();

        expect($form->formFields()->count())->toBe(1)
            ->and($form->formFields()->first()->getTranslation('label', 'lt'))->toBe('Naujas');
    });
});

describe('validation', function (): void {
    test('store requires valid data', function (): void {
        $data = [
            'name' => ['lt' => ''], // Missing required field
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            // Missing required 'path' and 'tenant_id' fields
        ];

        asUser($this->admin)
            ->post(route('forms.store'), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['path', 'tenant_id']);
    });

    test('update requires valid data', function (): void {
        $form = Form::factory()->for($this->tenant)->create();
        $data = [
            'name' => ['lt' => ''], // Missing required field
            // Missing required 'path' and 'tenant_id' fields
        ];

        asUser($this->admin)
            ->put(route('forms.update', $form), $data)
            ->assertStatus(302)
            ->assertSessionHasErrors(['path', 'tenant_id']);
    });
});
