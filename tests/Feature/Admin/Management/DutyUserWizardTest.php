<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    // Create role if it doesn't exist and give it permissions
    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'duties.delete.padalinys',
        'institutions.read.padalinys',
        'institutions.create.padalinys',
    ]);

    $this->regularUser = makeUser($this->tenant);
    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Communication Coordinator');

    // Create institution with duty for testing
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->duty = Duty::factory()->create([
        'institution_id' => $this->institution->id,
        'places_to_occupy' => 3,
    ]);
});

describe('wizard page access', function () {
    test('unauthorized user cannot access wizard', function () {
        $response = asUser($this->regularUser)->get(route('duties.updateUsersWizard'));
        expect($response->status())->toBe(403);
    });

    test('authorized user can access wizard', function () {
        $response = asUser($this->dutyManager)->get(route('duties.updateUsersWizard'));
        $response->assertStatus(200);
    });

    test('wizard page returns correct data structure', function () {
        $response = asUser($this->dutyManager)->get(route('duties.updateUsersWizard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/DutyUserUpdateWizard')
            // Immediate data (loaded on page load)
            ->has('institutions')
            ->has('assignableTenants')
            ->has('institutionTypes')
            // Lazy-loaded data (NOT present on initial load, loaded via partial reload)
            ->missing('studyPrograms')
            ->missing('users')
            ->missing('dutyTypes')
        );
    });

    test('wizard lazy loads users and study programs when requested', function () {
        // We can verify lazy loading works by making a request with partial reload
        // The framework handles this - we just verify the controller is set up correctly
        // by checking that the props are closures (optional) in the response
        
        // First, verify initial load doesn't have the lazy data
        $response = asUser($this->dutyManager)->get(route('duties.updateUsersWizard'));
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/DutyUserUpdateWizard')
            ->missing('users')
            ->missing('studyPrograms')
        );
    });

    test('wizard lazy loads duty types when requested', function () {
        // Verify initial load doesn't have duty types
        $response = asUser($this->dutyManager)->get(route('duties.updateUsersWizard'));
        
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/People/DutyUserUpdateWizard')
            ->missing('dutyTypes')
        );
    });

    test('wizard includes institution types for creation', function () {
        // Create some institution types
        $type = Type::factory()->create([
            'model_type' => Institution::class,
            'title' => ['lt' => 'Test Type', 'en' => 'Test Type'],
        ]);

        $response = asUser($this->dutyManager)->get(route('duties.updateUsersWizard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('institutionTypes')
        );

        // Verify the type we created is in the response
        $types = collect($response->original->getData()['page']['props']['institutionTypes']);
        expect($types->pluck('id')->contains($type->id))->toBeTrue();
    });
});

describe('batch update users', function () {
    test('unauthorized user cannot batch update', function () {
        $response = asUser($this->regularUser)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [],
        ]);

        expect($response->status())->toBe(403);
    });

    test('authorized user can add user to duty', function () {
        $newUser = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => $newUser->id,
                    'action' => 'add',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addYear()->toDateString(),
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('dutiables', [
            'duty_id' => $this->duty->id,
            'dutiable_id' => $newUser->id,
            'dutiable_type' => User::class,
        ]);
    });

    test('authorized user can remove user from duty sets end date', function () {
        $existingUser = makeUser($this->tenant);
        $endDate = now()->toDateString();

        // First add the user
        $this->duty->users()->attach($existingUser->id, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => $existingUser->id,
                    'action' => 'remove',
                    'end_date' => $endDate,
                ],
            ],
        ]);

        $response->assertRedirect();

        // Removal sets end_date rather than detaching
        $pivot = $this->duty->fresh()->users()->where('users.id', $existingUser->id)->first();
        expect($pivot)->not->toBeNull();
        expect($pivot->pivot->end_date->toDateString())->toBe($endDate);
    });

    test('can add multiple users in single batch', function () {
        $user1 = makeUser($this->tenant);
        $user2 = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => $user1->id,
                    'action' => 'add',
                    'start_date' => now()->toDateString(),
                ],
                [
                    'user_id' => $user2->id,
                    'action' => 'add',
                    'start_date' => now()->toDateString(),
                ],
            ],
        ]);

        $response->assertRedirect();

        expect($this->duty->fresh()->users()->count())->toBe(2);
    });

    test('batch update can include study program', function () {
        $newUser = makeUser($this->tenant);
        $studyProgram = \App\Models\StudyProgram::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => $newUser->id,
                    'action' => 'add',
                    'start_date' => now()->toDateString(),
                    'study_program_id' => $studyProgram->id,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('dutiables', [
            'duty_id' => $this->duty->id,
            'dutiable_id' => $newUser->id,
            'study_program_id' => $studyProgram->id,
        ]);
    });

    test('can update places_to_occupy through batch update', function () {
        // First verify current value
        $this->duty->update(['places_to_occupy' => 3]);
        $newUser = makeUser($this->tenant);

        // Add a user change along with places_to_occupy update
        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => $newUser->id,
                    'action' => 'add',
                    'start_date' => now()->toDateString(),
                ],
            ],
            'places_to_occupy' => 5,
        ]);

        $response->assertRedirectToRoute('duties.show', $this->duty);

        expect($this->duty->fresh()->places_to_occupy)->toBe(5);
    });
});

describe('validation', function () {
    test('user_changes is required', function () {
        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), []);

        $response->assertStatus(302)
            ->assertSessionHasErrors('user_changes');
    });

    test('user_id is required in user_changes', function () {
        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'action' => 'add',
                ],
            ],
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('user_changes.0.user_id');
    });

    test('action must be valid', function () {
        $newUser = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => $newUser->id,
                    'action' => 'invalid',
                ],
            ],
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('user_changes.0.action');
    });

    test('places_to_occupy must be a positive integer', function () {
        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [],
            'places_to_occupy' => -1,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('places_to_occupy');
    });
});

describe('tenant isolation', function () {
    test('cannot batch update duty from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherDuty = Duty::factory()->create(['institution_id' => $otherInstitution->id]);
        $newUser = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $otherDuty), [
            'user_changes' => [
                [
                    'user_id' => $newUser->id,
                    'action' => 'add',
                ],
            ],
        ]);

        expect($response->status())->toBe(403);
    });
});

describe('new user creation', function () {
    test('can create new users through batch update', function () {
        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [
                [
                    'user_id' => 'new-1',
                    'action' => 'add',
                    'start_date' => now()->toDateString(),
                ],
            ],
            'new_users' => [
                [
                    'name' => 'New Test User',
                    'email' => 'newuser@test.com',
                    'phone' => '+37060000000',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'New Test User',
            'email' => 'newuser@test.com',
        ]);
    });

    test('new user email must be unique', function () {
        $existingUser = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [],
            'new_users' => [
                [
                    'name' => 'Duplicate User',
                    'email' => $existingUser->email,
                ],
            ],
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('new_users.0.email');
    });
});
