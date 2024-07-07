<?php

use App\Actions\GetAttachableTypesForDuty;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->padalinys = Padalinys::query()->inRandomOrder()->first();

    $this->simpleUser = User::factory()->hasAttached(Duty::factory()->for(Institution::factory()->for($this->padalinys)),
        ['start_date' => now()->subDay()]
    )->create();

    $this->contactManagerUser = User::factory()->hasAttached(Duty::factory()->for(Institution::factory()->for($this->padalinys)),
        ['start_date' => now()->subDay()]
    )->create();

    $this->contactManagementDuty = $this->contactManagerUser->duties()->first();

    $this->contactManagementDuty->assignRole('Student Representative Coordinator');
});

test('simple user cant access all users in admin', function () {
    $user = $this->simpleUser;

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertStatus(302)->assertRedirectToRoute('dashboard');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowDashboard')
            ->where('flash.statusCode', 403)
        );
});

test('simple user cant create contact', function () {
    $user = $this->simpleUser;

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this->actingAs($user)->get(route('users.create'));

    $response->assertStatus(302)->assertRedirectToRoute('dashboard');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowDashboard')
            ->where('flash.statusCode', 403)
        );

    $response = $this->actingAs($user)->post(route('users.store'), [
        'name' => 'Test 1',
        'email' => 'test@email.com',
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('dashboard');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ShowDashboard')
            ->where('flash.statusCode', 403)
        );

    $this->assertDatabaseMissing('users', [
        'name' => 'Test 1',
    ]);
});

test('contact manager cant create user without duty', function () {
    $user = $this->contactManagerUser;

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this->actingAs($user)->get(route('users.create'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/CreateUser')
        ->has('roles')
        ->has('padaliniaiWithDuties')
    );

    $response = $this->actingAs($user)->post(route('users.store'), [
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
    $user = $this->contactManagerUser;

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this->actingAs($user)->get(route('users.create'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/CreateUser')
        ->has('roles')
        ->has('padaliniaiWithDuties')
    );

    $response = $this->actingAs($user)->post(route('users.store'), [
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
    $admin = $this->contactManagerUser;
    $user = $this->simpleUser;

    $duty = $this->simpleUser->current_duties->first();

    $this->actingAs($admin)->get(route('dashboard'));

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexUser')
        ->has('users.data')
    );

    $response = $this->actingAs($admin)->get(route('users.edit', $user->id))->assertStatus(200);

    $this->followRedirects($response)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/EditUser')
    );

    $response = $this->actingAs($admin)->patch(route('users.update', $user->id), [
        'name' => 'Test 4',
        'email' => $user->email,
        'current_duties' => [],
    ]);

    $response->assertStatus(302)->assertRedirectToRoute('users.edit', $user->id);

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/EditUser')
            ->has('flash.success')
            ->has('user.current_duties', 0)
            ->has('user.previous_duties', 1)
        );

    // get dutiable
    $dutiable = $user->dutiables->where('duty_id', $duty->id)->first();

    // check if has end_date
    expect($dutiable->end_date)->not->toBeNull();
});

test('contact manager can delete and restore user', function () {
    $admin = $this->contactManagerUser;
    $user = $this->simpleUser;

    $this->actingAs($admin)->get(route('dashboard'));

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexUser')
        ->has('users.data')
    );

    $response = $this->actingAs($admin)->delete(route('users.destroy', $user->id));

    $response->assertStatus(302)->assertRedirectToRoute('users.index');

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->has('flash.success')
        );

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);

    $response = $this->actingAs($admin)->get(route('users.index', ['showSoftDeleted' => true]));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexUser')
        ->has('users.data')
    );

    $response = $this->actingAs($admin)->patch(route('users.restore', $user->id));

    $response->assertStatus(302)->assertRedirectToRoute('users.index', ['showSoftDeleted' => true]);

    $this->followRedirects($response)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/IndexUser')
            ->has('flash.success')
        );

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});

test('contact manager can add type to duty', function () {
    $admin = $this->contactManagerUser;
    $user = $this->simpleUser;
    $userDuty = $user->current_duties->first();

    $this->assertDatabaseHas('roles', [
        'name' => 'Student Representative',
    ]);

    $this->assertDatabaseHas('types', [
        'slug' => 'studentu-atstovai',
    ]);

    $this->actingAs($admin)->get(route('dashboard'));

    $response = $this->actingAs($admin)->get(route('duties.index'));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/IndexDuty')
        ->has('duties.data')
    );

    $response = $this->actingAs($admin)->get(route('duties.edit', $userDuty->id));

    $response->assertStatus(200)->assertInertia(fn (Assert $page) => $page
        ->component('Admin/People/EditDuty')
        ->has('duty')
        ->has('dutyTypes')
    );

    $types = [];

    foreach (GetAttachableTypesForDuty::execute() as $type) {
        array_push($types, $type->id);
    }

    $response = $this->actingAs($admin)->patch(route('duties.update', $userDuty->id), [
        'name' => $userDuty->name,
        'users' => [$user->id],
        'institution_id' => $userDuty->institution_id,
        'places_to_occupy' => $userDuty->places_to_occupy,
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
        'type_id' => GetAttachableTypesForDuty::execute()->first()->id,
    ]);

    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => Role::query()->where('name', 'Student Representative')->first()->id,
        'model_type' => get_class($userDuty),
        'model_id' => $userDuty->id,
    ]);

    $response = $this->actingAs($admin)->patch(route('duties.update', $userDuty->id), [
        'name' => $userDuty->name,
        'users' => [$user->id],
        'institution_id' => $userDuty->institution_id,
        'places_to_occupy' => $userDuty->places_to_occupy,
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
        'type_id' => GetAttachableTypesForDuty::execute()->first()->id,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'role_id' => Role::query()->where('name', 'Student Representative')->first()->id,
        'model_type' => get_class($userDuty),
        'model_id' => $userDuty->id,
    ]);
});

// LAST. Check if the public returned users are ONLY current users in PUBLIC
