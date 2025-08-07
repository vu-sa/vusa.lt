<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

describe('unauthorized access', function () {
    test('cannot access index page', function () {
        asUser($this->user)
            ->get(route('forms.index'))
            ->assertStatus(403);
    });

    test('cannot view form', function () {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->user)
            ->get(route('forms.show', $form))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)
            ->get(route('forms.create'))
            ->assertStatus(403);
    });

    test('cannot store form', function () {
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

    test('cannot access edit page', function () {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->user)
            ->get(route('forms.edit', $form))
            ->assertStatus(403);
    });

    test('cannot update form', function () {
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

    test('cannot delete form', function () {
        $form = Form::factory()->for($this->tenant)->create();

        asUser($this->user)
            ->delete(route('forms.destroy', $form))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access index page', function () {
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

    test('index shows forms from all tenants for super admin', function () {
        // Clear any existing forms to ensure clean test
        Form::query()->delete();

        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'My Form', 'en' => 'My Form']]);
        Form::factory()->for($otherTenant)->create(['name' => ['lt' => 'Other Form', 'en' => 'Other Form']]);

        // Use Super Admin user for cross-tenant access
        $superAdmin = makeAdminUser();

        asUser($superAdmin)
            ->get(route('forms.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('forms.data', 2) // Super Admin sees all forms
                ->where('forms.data.0.name', 'My Form')
                ->where('forms.data.1.name', 'Other Form')
            );
    });

    test('index supports search filtering', function () {
        // Clear any existing forms to ensure clean test
        Form::query()->delete();

        Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Important Form', 'en' => 'Important Form']]);
        Form::factory()->for($this->tenant)->create(['name' => ['lt' => 'Regular Form', 'en' => 'Regular Form']]);

        asUser($this->admin)
            ->get(route('forms.index', ['text' => 'Important']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('forms.data', 1)
                ->where('forms.data.0.name', 'Important Form')
            );
    });

    test('can view form', function () {
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

    test('can view form from different tenant as super admin', function () {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $form = Form::factory()->for($otherTenant)->create();

        $superAdmin = makeAdminUser();

        asUser($superAdmin)
            ->get(route('forms.show', $form))
            ->assertStatus(200); // Super Admin can access any tenant's forms
    });

    test('can access create page', function () {
        asUser($this->admin)
            ->get(route('forms.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Forms/CreateForm')
            );
    });

    test('can access edit page', function () {
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

    test('can edit form from different tenant as super admin', function () {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $form = Form::factory()->for($otherTenant)->create();

        $superAdmin = makeAdminUser();

        asUser($superAdmin)
            ->get(route('forms.edit', $form))
            ->assertStatus(200); // Super Admin can access any tenant's forms
    });

    test('can delete form', function () {
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

describe('validation', function () {
    test('store requires valid data', function () {
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

    test('update requires valid data', function () {
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
