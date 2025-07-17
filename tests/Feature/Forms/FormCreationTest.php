<?php

use App\Models\Form;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->user = makeUser($this->tenant);

    $this->manager = makeFormManager($this->tenant);

    Form::factory()->count(5)->recycle($this->tenant)->create();
});

function makeFormManager($tenant): User
{
    $user = makeUser($tenant);

    $role = Role::create(['name' => 'Form Manager']);

    $role->givePermissionTo('forms.create.padalinys');

    $user->duties()->first()->assignRole('Form Manager');

    return $user;
}

describe('auth: simple user', function () {
    beforeEach(fn () => asUser($this->user)->get(route('dashboard'))->assertStatus(200));

    test('can\'t index forms', function () {
        asUser($this->user)->get(route('forms.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t access form create page', function () {
        asUser($this->user)->get(route('forms.create'))->assertStatus(302);
    });

    test('can\'t store form', function () {
        asUser($this->user)->post(route('forms.store'), [
            'title' => 'Test form',
            'description' => 'Test form description',
        ])->assertStatus(302);
    });

    test('can\'t access the form edit page', function () {
        $form = Form::query()->first();

        asUser($this->user)->get(route('forms.edit', $form))->assertStatus(302);
    });

    test('can\'t update form', function () {
        $form = Form::query()->first();

        asUser($this->user)->put(route('forms.update', $form), [
            'title' => 'Test form updated',
            'description' => 'Test form description updated',
        ])->assertStatus(302);
    });

    test('can\'t delete form', function () {
        $form = Form::query()->first();

        asUser($this->user)->delete(route('forms.destroy', $form))->assertStatus(302);
    });
});
