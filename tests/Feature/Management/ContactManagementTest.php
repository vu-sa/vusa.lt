<?php

use App\Actions\GetAttachableTypesForDuty;
use App\Models\Duty;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);


beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->user = makeUser($this->tenant);

    $this->admin = makeContactManager($this->tenant);
});

function makeContactManager($tenant): User
{
    $user = makeUser($tenant);

    $user->duties()->first()->assignRole('Student Representative Coordinator');

    return $user;
}

test('simple user can\'t access all users', function () {
    $user = asUser($this->user);

    $user->get(route('dashboard'));

    $response = $user->get(route('users.index'));

    $response->assertStatus(302)->assertRedirectToRoute('dashboard');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowAdminHome')
            ->where('flash.statusCode', 403)
        );
});

test('simple user can\'t create contact', function () {
    $user = asUser($this->user);

    $user->get(route('dashboard'));

    $response = $user->get(route('users.create'));

    $response->assertStatus(302)->assertRedirectToRoute('dashboard');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowAdminHome')
            ->where('flash.statusCode', 403)
        );

    $response = $user->post(route('users.store'), [
        'name' => 'Test 1',
        'email' => 'test@email.com',
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('dashboard');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowAdminHome')
            ->where('flash.statusCode', 403)
        );

    $this->assertDatabaseMissing('users', [
        'name' => 'Test 1',
    ]);
});

test('contact manager can\'t create user without duty', function () {
    $admin = asUser($this->admin);

    $admin->get(route('dashboard'));

    $response = $admin->get(route('users.create'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/CreateUser')
        ->has('roles')
        ->has('tenantsWithDuties')
    );

    $response = $admin->post(route('users.store'), [
        'name' => 'Test 2',
        'email' => 'test@email.com',
        'current_duties' => [],
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('users.create');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/CreateUser')
            ->whereNot('errors.current_duties', null)
        );

    $this->assertDatabaseMissing('users', [
        'name' => 'Test 2',
    ]);
});

test('contact manager can create user with duty', function () {
    $admin = asUser($this->admin);

    $admin->get(route('dashboard'));

    $response = $admin->get(route('users.create'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/CreateUser')
        ->has('roles')
        ->has('tenantsWithDuties')
    );

    $response = $admin->post(route('users.store'), [
        'name' => 'Test 3',
        'email' => 'test@email.com',
        'current_duties' => [
            [
                'duty_id' => Duty::inRandomOrder()->first()->id,
            ],
        ],
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('users.index');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->has('flash.success')
        );

    $this->assertDatabaseHas('users', [
        'name' => 'Test 3',
    ]);
});

test('contact manager can detach duty from user', function () {
    $admin = asUser($this->admin);

    $duty = $this->user->current_duties->first();

    $admin->get(route('dashboard'));

    $response = $admin->get(route('users.index'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexUser')
        ->has('users.data')
    );

    $response = $admin->get(route('users.edit', $this->user->id))->assertStatus(200);

    $this->followRedirects($response)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/EditUser')
    );

    $response = $admin->patch(route('users.update', $this->user->id), [
        'name' => 'Test 4',
        'email' => $this->user->email,
        'current_duties' => [],
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('users.edit', $this->user->id);

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/EditUser')
            ->has('flash.success')
            ->has('user.current_duties', 0)
            ->has('user.previous_duties', 1)
        );

    // get dutiable
    $dutiable = $this->user->dutiables->where('duty_id', $duty->id)->first();

    // check if has end_date
    expect($dutiable->end_date)->not->toBeNull();
});

test('contact manager can delete and restore user', function () {
    $admin = asUser($this->admin);

    $admin->get(route('dashboard'));

    $response = $admin->get(route('users.index'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexUser')
        ->has('users.data')
    );

    $response = $admin->delete(route('users.destroy', $this->user->id));

    $response->assertStatus(302)->assertRedirectToRoute('users.index');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->has('flash.success')
        );

    $this->assertSoftDeleted('users', [
        'id' => $this->user->id,
    ]);

    $response = $admin->get(route('users.index', ['showSoftDeleted' => true]));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexUser')
        ->has('users.data')
    );

    $response = $admin->patch(route('users.restore', $this->user->id));

    $response->assertStatus(302)->assertRedirectToRoute('users.index', ['showSoftDeleted' => true]);

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->has('flash.success')
        );

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
    ]);
})->skip('Deleting users is disabled almost for all');

test('contact manager can add type to duty', function () {
    $admin = asUser($this->admin);
    $userDuty = $this->user->current_duties->first();

    $this->assertDatabaseHas('roles', [
        'name' => 'Student Representative',
    ]);

    $this->assertDatabaseHas('types', [
        'slug' => 'studentu-atstovai',
    ]);

    $admin->get(route('dashboard'));

    $response = $admin->get(route('duties.index'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexDuty')
        ->has('duties.data')
    );

    $response = $admin->get(route('duties.edit', $userDuty->id));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/EditDuty')
        ->has('duty')
        ->has('dutyTypes')
    );

    $availableTypes = GetAttachableTypesForDuty::execute();
    $types = $availableTypes->pluck('id')->toArray();
    $firstTypeId = $availableTypes->first()->id;

    $response = $admin->patch(route('duties.update', $userDuty->id), [
        'name' => $userDuty->name,
        'current_users' => [$this->user->id],
        'institution_id' => $userDuty->institution_id,
        'places_to_occupy' => $userDuty->places_to_occupy,
        'contacts_grouping' => $userDuty->contacts_grouping ?? 'none',
        'types' => $types,
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('duties.edit', $userDuty->id);

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/EditDuty')
            ->has('flash.success')
        );

    $this->assertDatabaseHas('typeables', [
        'typeable_id' => $userDuty->id,
        'typeable_type' => get_class($userDuty),
        'type_id' => $firstTypeId,
    ]);

    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => Role::query()->where('name', 'Student Representative')->first()->id,
        'model_type' => get_class($userDuty),
        'model_id' => $userDuty->id,
    ]);

    $response = $admin->patch(route('duties.update', $userDuty->id), [
        'name' => $userDuty->name,
        'current_users' => [$this->user->id],
        'institution_id' => $userDuty->institution_id,
        'places_to_occupy' => $userDuty->places_to_occupy,
        'contacts_grouping' => $userDuty->contacts_grouping ?? 'none',
        'types' => [],
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('duties.edit', $userDuty->id);

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/EditDuty')
            ->has('flash.success')
        );

    $this->assertDatabaseMissing('typeables', [
        'typeable_id' => $userDuty->id,
        'typeable_type' => get_class($userDuty),
        'type_id' => $firstTypeId,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'role_id' => Role::query()->where('name', 'Student Representative')->first()->id,
        'model_type' => get_class($userDuty),
        'model_id' => $userDuty->id,
    ]);
});

// LAST. Check if the public returned users are ONLY current users in PUBLIC
