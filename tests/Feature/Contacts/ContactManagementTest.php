<?php

namespace Tests\Feature\Contacts;

use App\Actions\GetAttachableTypesForDuty;
use App\Models\Duty;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ContactManagementTest extends ContactTestCase
{
    // 1. Test if simple user can't access all contacts in admin

    public function test_simple_user_cant_access_all_users_in_admin()
    {
        $user = $this->simpleUser;

        $this->actingAs($user)->get(route('dashboard'));

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(302)->assertRedirectToRoute('dashboard');

        $this->followRedirects($response)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowDashboard')
                ->where('flash.statusCode', 403)
            );
    }

    // 2. Test if simple user can't go to contact create page and create contact

    public function test_simple_user_cant_create_contact()
    {
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
    }

    // 3. Test if admin can create user without duty

    public function test_contact_manager_cant_create_user_without_duty()
    {
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
    }

    // 4. Test if admin can create user and assign duty to them

    public function test_contact_manager_can_create_user_with_duty()
    {
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
    }

    // 5. Test if admin can detach duty from user and check if the duty is not detached, but has end_date set to today
    // And also check if the duty is in previous_duties, but not in current_duties

    public function test_contact_manager_can_detach_duty_from_user()
    {
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

        $this->assertDatabaseHas('dutiables', [
            'dutiable_id' => $user->id,
            'dutiable_type' => get_class($user),
            'duty_id' => $duty->id,
            // to YYYY-MM-DD
            'end_date' => now()->subDay(),
        ]);
    }

    // 6. Check if admin can delete an user and if it is soft-deleted, and if it can be restored

    public function test_contact_manager_can_delete_and_restore_user()
    {
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

        $response->assertStatus(302)->assertRedirectToRoute('users.index');

        $this->followRedirects($response)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/IndexUser')
                ->has('flash.success')
            );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    // 7. Check if admin can add a type to user and when added, if a role is added automatically

    // * This is A STUDENT REPRESENTATIVE CASE, it may be needed to be generalized more
    public function test_contact_manager_can_add_type_to_duty()
    {
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
    }

    // LAST. Check if the public returned users are ONLY current users in PUBLIC
}
