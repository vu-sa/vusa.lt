<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\ResourceServices\UserDutyService;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
});

describe('unauthorized access', function (): void {
    beforeEach(function (): void {
        $this->user = makeUser($this->tenant);
        $response = asUser($this->user)->get(route('dashboard'));
        expect($response->status())->toBeSecureResponse();
    });

    test('cannot index users', function (): void {
        $response = asUser($this->user)->get(route('users.index'));
        expect($response->status())->toRequireAuth();
    });

    test('cannot access user create page', function (): void {
        $response = asUser($this->user)->get(route('users.create'));
        expect($response->status())->toRequireAuth();
    });

    test('cannot store user', function (): void {
        $response = asUser($this->user)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        expect($response->status())->toRequireAuth();
    });

    test('cannot access user edit page', function (): void {
        $user = User::factory()->create();
        $response = asUser($this->user)->get(route('users.edit', $user));
        expect($response->status())->toRequireAuth();
    });

    test('cannot update user', function (): void {
        $user = User::factory()->create();
        $response = asUser($this->user)->put(route('users.update', $user), [
            'name' => 'Test User Updated',
            'email' => 'updated@example.com',
        ]);
        expect($response->status())->toRequireAuth();
    });

    test('cannot delete user', function (): void {
        $user = User::factory()->create();
        $response = asUser($this->user)->delete(route('users.destroy', $user));
        expect($response->status())->toRequireAuth();
    });
});

describe('authorized access', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('can index users', function (): void {
        User::factory()->count(3)->create();

        $response = asUser($this->admin)->get(route('users.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexUser')
                ->has('users')
            );
    });

    test('can access user create page', function (): void {
        $response = asUser($this->admin)->get(route('users.create'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/CreateUser')
            );
    });

    test('can store user', function (): void {
        // The duty must sit in the admin's own tenant — Duty::factory() would
        // otherwise build its own Institution and Tenant, which UserDutyService
        // now rejects.
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+370 123 4567',
            'current_duties' => [$duty->id],
        ];

        $response = asUser($this->admin)->post(route('users.store'), $userData);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    });

    test('can access user edit page', function (): void {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->get(route('users.edit', $user));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/EditUser')
                ->has('user')
                ->where('user.id', $user->id)
            );
    });

    test('current duty pivot exposes study_program_id so the admin form can flag a missing one', function (): void {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->get(route('users.edit', $user));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/EditUser')
                ->has('user.current_duties.0.pivot.study_program_id')
            );
    });

    test('edit page attributes each duty to its institution, not just a bare name', function (): void {
        // A duty name alone is unattributable once it repeats across institutions
        // (it does constantly, e.g. "Studentų atstovas") — the admin form needs
        // the institution to tell two "Vadovas" duties apart.
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();

        $response = asUser($this->admin)->get(route('users.edit', $user));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/EditUser')
                ->where('user.current_duties.0.id', $duty->id)
                ->where('user.current_duties.0.institution.tenant.shortname', $duty->institution->tenant->shortname)
            );
    });

    test('show page passes per-record update and delete capabilities', function (): void {
        // The page gates its edit and delete controls on these, since `auth.can`
        // carries no flat permission names to gate them with.
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->get(route('users.show', $user));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowUser')
                ->where('can.update', true)
                ->has('can.delete')
            );
    });

    test('can update user', function (): void {
        $user = makeUser($this->tenant);

        $updateData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'phone' => '+370 987 6543',
        ];

        $response = asUser($this->admin)->put(route('users.update', $user), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ]);
    });

    test('can delete user', function (): void {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->delete(route('users.destroy', $user));

        $response->assertRedirect();

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    });
});

describe('validation', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('requires name for store', function (): void {
        $response = asUser($this->admin)->post(route('users.store'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name');
    });

    test('requires email for store', function (): void {
        $response = asUser($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires valid email format for store', function (): void {
        $response = asUser($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires unique email for store', function (): void {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = asUser($this->admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires name for update', function (): void {
        $user = makeUser($this->tenant);

        $response = asUser($this->admin)->put(route('users.update', $user), [
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name');
    });

    test('requires unique email for update', function (): void {
        $existingUser = makeUser($this->tenant);
        $existingUser->update(['email' => 'existing@example.com']);
        $user = makeUser($this->tenant);
        $user->update(['email' => 'user@example.com']);

        $response = asUser($this->admin)->put(route('users.update', $user), [
            'name' => 'Updated User',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });
});

describe('relationships', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('user has proper model structure', function (): void {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+370 123 4567',
        ]);

        expect($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@example.com')
            ->and($user->phone)->toBe('+370 123 4567');
    });

    test('can retrieve user by email', function (): void {
        $user = User::factory()->create(['email' => 'unique@example.com']);

        $foundUser = User::where('email', 'unique@example.com')->first();

        expect($foundUser)->not->toBeNull()
            ->and($foundUser->id)->toBe($user->id);
    });

    test('user can have duties', function (): void {
        $user = User::factory()->create();

        // Check if user can have duties (relationship exists)
        expect($user->duties())->toBeInstanceOf(BelongsToMany::class);
    });
});

describe('duty removal', function (): void {
    beforeEach(function (): void {
        $this->admin = makeUser($this->tenant);
        $this->admin->assignRole(config('permission.super_admin_role_name'));
    });

    test('removing a duty held across multiple dutiable rows ends the active row', function (): void {
        $target = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        // Same duty across two periods: an old ended one plus a current one.
        $target->duties()->attach($duty->id, ['start_date' => now()->subYears(2), 'end_date' => now()->subYear()]);
        $target->duties()->attach($duty->id, ['start_date' => now()->subDay(), 'end_date' => null]);

        $activeCount = fn () => Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('dutiable_id', $target->id)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->count();

        expect($activeCount())->toBe(1);

        asUser($this->admin)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'current_duties' => [],
        ])->assertRedirect();

        // The active row must be end-dated even though an older ended row exists.
        expect($activeCount())->toBe(0);
    });
});

describe('duty assignment', function (): void {
    beforeEach(function (): void {
        $this->admin = makeUser($this->tenant);
        $this->admin->assignRole(config('permission.super_admin_role_name'));
    });

    test('re-adding a duty the user already actively holds does not create a second active row', function (): void {
        $target = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        // The user is already active on the duty.
        $target->duties()->attach($duty->id, ['start_date' => now()->subDay(), 'end_date' => null]);

        // syncDutiesForUser resolves the actor's tenants through Auth, so log in.
        Auth::login($this->admin);

        // Simulate a stale snapshot: the duty is passed as "new" even though an
        // active row already exists. The guard must keep it to a single row.
        UserDutyService::syncDutiesForUser(
            new Collection([$duty->id]),
            new Collection([]),
            $target,
            app(ModelAuthorizer::class),
            'users.update.all',
        );

        $activeCount = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('dutiable_id', $target->id)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->count();

        expect($activeCount)->toBe(1);
    });
});

describe('create-form field coverage', function (): void {
    beforeEach(function (): void {
        $this->admin = makeAdminUser($this->tenant);
        $this->duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    });

    test('pronouns submitted on create are persisted', function (): void {
        // UserForm renders the pronouns section in create mode too, but store() fills
        // from $request->safe(), so an unvalidated field is silently dropped.
        asUser($this->admin)->post(route('users.store'), [
            'name' => 'Pronouns Person',
            'email' => 'pronouns@stud.vu.lt',
            'pronouns' => ['lt' => 'Jie/jų', 'en' => 'They/them'],
            'show_pronouns' => true,
            'current_duties' => [$this->duty->id],
        ])->assertRedirect();

        $created = User::query()->where('email', 'pronouns@stud.vu.lt')->firstOrFail();

        expect($created->getTranslation('pronouns', 'lt'))->toBe('Jie/jų')
            ->and($created->getTranslation('pronouns', 'en'))->toBe('They/them')
            ->and($created->show_pronouns)->toBeTrue();
    });

    test('a non-existent role id on create is rejected rather than synced', function (): void {
        asUser($this->admin)->post(route('users.store'), [
            'name' => 'Bogus Role',
            'email' => 'bogus.role@stud.vu.lt',
            'roles' => [999999],
            'current_duties' => [$this->duty->id],
        ])->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'bogus.role@stud.vu.lt']);
    });

    test('a super admin can still assign a real role on create', function (): void {
        $role = Role::firstOrCreate(['name' => 'Create Path Role', 'guard_name' => 'web']);

        asUser($this->admin)->post(route('users.store'), [
            'name' => 'Real Role',
            'email' => 'real.role@stud.vu.lt',
            'roles' => [$role->id],
            'current_duties' => [$this->duty->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(User::query()->where('email', 'real.role@stud.vu.lt')->firstOrFail()->hasRole($role))->toBeTrue();
    });
});
