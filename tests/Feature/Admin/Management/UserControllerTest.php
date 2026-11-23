<?php

use App\Actions\Duties\SyncUserDuties;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Pivots\Dutiable;
use App\Models\Problem;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
});

describe('unauthorized access', function (): void {
    beforeEach(function (): void {
        $this->user = makeUser($this->tenant);
        asUser($this->user)->get(route('dashboard'))->assertOk();
    });

    test('cannot index users', function (): void {
        asUser($this->user)->get(route('users.index'))->assertForbidden();
    });

    test('cannot access user create page', function (): void {
        asUser($this->user)->get(route('users.create'))->assertForbidden();
    });

    test('cannot store user', function (): void {
        asUser($this->user)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assertForbidden();
    });

    test('cannot access user edit page', function (): void {
        $user = User::factory()->create();
        asUser($this->user)->get(route('users.edit', $user))->assertForbidden();
    });

    test('cannot update user', function (): void {
        $user = User::factory()->create();
        asUser($this->user)->put(route('users.update', $user), [
            'name' => 'Test User Updated',
            'email' => 'updated@example.com',
        ])->assertForbidden();
    });

    test('cannot delete user', function (): void {
        $user = User::factory()->create();
        asUser($this->user)->delete(route('users.destroy', $user))->assertForbidden();
    });
});

describe('authorized access', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    test('can index users', function (): void {
        User::factory()->count(3)->create();

        $response = asUser($this->admin)->get(route('users.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexUser')
                ->has('users')
                ->has('deletedCount')
            );
    });

    test('index query count stays constant as more user rows are returned', function (string $routeName, string $itemsPath): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        User::factory()->count(10)->hasAttached($duty, ['start_date' => now()->subDay()])->create();

        $fetch = fn (int $perPage) => asUserWithInertia($this->admin)
            ->getJson(route($routeName, ['per_page' => $perPage]))->assertOk();

        // Warm request-level infrastructure before comparing the two page sizes.
        $fetch(3);
        $fetch(10);

        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $small = $fetch(3);
            $smallCount = count(DB::getQueryLog());
            DB::flushQueryLog();
            $large = $fetch(10);
            $largeCount = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $small->assertJsonCount(3, $itemsPath);
        $large->assertJsonCount(10, $itemsPath)
            ->assertJsonPath($itemsPath.'.0.can_update_identity', true)
            ->assertJsonMissingPath($itemsPath.'.0.roles')
            ->assertJsonMissingPath($itemsPath.'.0.tenants');
        expect($largeCount)->toBe($smallCount);
    })->with([
        'page' => ['users.index', 'props.users.data'],
        'API' => ['api.v1.admin.users.index', 'data.items'],
    ]);

    test('the create form offers no duties from a unit where the actor only held an ended term', function (): void {
        $formerTenant = Tenant::factory()->create();
        $formerDuty = Duty::factory()->for(Institution::factory()->for($formerTenant))->create();
        $this->admin->duties()->attach($formerDuty->id, ['start_date' => '2019-07-01', 'end_date' => '2020-06-30']);

        asUser($this->admin)->get(route('users.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tenantsWithDuties', fn ($tenants) => collect($tenants)->pluck('id')->contains($this->tenant->id)
                    && ! collect($tenants)->pluck('id')->contains($formerTenant->id)));
    });

    test('separates current members from former ones', function (): void {
        $current = makeUser($this->tenant);
        $former = makeUser($this->tenant);
        $former->dutiables()->update(['start_date' => '2019-07-01', 'end_date' => '2020-06-30']);

        $listed = fn (string $membership) => collect(asUser($this->admin)
            ->getJson(route('api.v1.admin.users.index', ['membership' => $membership]))
            ->assertOk()
            ->json('data.items'))->keyBy('id');

        expect($listed('current')->keys())->toContain($current->id)->not->toContain($former->id)
            ->and($listed('former')->keys())->toContain($former->id)->not->toContain($current->id)
            ->and($listed('former')[$former->id]['is_current'])->toBeFalse()
            ->and(substr($listed('former')[$former->id]['last_term_end'], 0, 10))->toBe('2020-06-30')
            ->and($listed('current')[$current->id]['current_duties_count'])->toBe(1)
            // The row's quick list names where each term was held.
            ->and($listed('former')[$former->id]['duties'][0]['institution']['name'])->toBe($former->duties()->first()->institution->name);
    });

    test('the trash is the same collection over soft-deleted members', function (): void {
        $gone = makeUser($this->tenant);
        $gone->delete();

        asUserWithInertia($this->admin)->get(route('users.index', ['showDeleted' => 'true']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexUser')
                ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($gone->id)));
    });

    test('can access user create page', function (): void {
        $response = asUser($this->admin)->get(route('users.create'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/CreateUser')
            );
    });

    test('coordinator opens the newly created user', function (): void {
        $actor = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+370 123 4567',
            'current_duties' => [$duty->id],
        ];

        $response = asUser($actor)->post(route('users.store'), $userData);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $created = User::where('email', 'test@example.com')->sole();
        $response->assertRedirectToRoute('users.show', $created)->assertSessionHas('success');
        asUser($actor)->get(route('users.show', $created))->assertOk();
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

    // Duties are an association with their own lifecycle: they are managed on the record (the
    // Priskirti sheet), so the form is sent none of the picker payloads.
    test('the edit form carries the person, not the duty tree or the role list', function (): void {
        $user = makeUser($this->tenant);

        asUserWithInertia($this->admin)->get(route('users.edit', $user))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/EditUser')
                ->has('user.current_duties.0.id')
                ->has('canUpdateIdentity')
                ->missing('tenantsWithDuties')
                ->missing('permissableTenants')
                ->missing('roles'));
    });

    test('the login time shows only as a band, never the exact time', function (): void {
        $user = makeUser($this->tenant);
        $user->forceFill(['last_action' => now()->subDays(10)])->save();

        asUser($this->admin)->get(route('users.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($rows): bool => collect($rows)->firstWhere('id', $user->id)['login_recency'] === 'month'
                && ! array_key_exists('last_action', collect($rows)->firstWhere('id', $user->id))));
        asUser($this->admin)->get(route('users.show', $user))->assertOk()->assertInertia(fn ($page) => $page
            ->where('user.login_recency', 'month')->missing('user.last_action'));
        asUserWithInertia($this->admin)->get(route('users.edit', $user))->assertOk()->assertInertia(fn ($page) => $page
            ->where('user.login_recency', 'month')->missing('user.last_action'));
    });

    test('show and edit pages never send the password or remember token', function (): void {
        $user = makeUser($this->tenant);
        $user->forceFill(['password' => bcrypt('slaptas'), 'remember_token' => 'token'])->save();

        asUser($this->admin)->get(route('users.show', $user))->assertOk()->assertInertia(fn ($page) => $page
            ->missing('user.password')->missing('user.remember_token'));
        asUserWithInertia($this->admin)->get(route('users.edit', $user))->assertOk()->assertInertia(fn ($page) => $page
            ->missing('user.password')->missing('user.remember_token'));
    });

    test('search treats quotes and SQL as plain text', function (): void {
        $user = makeUser($this->tenant);
        $user->update(['name' => "Vardenis O'Brien"]);

        asUser($this->admin)->get(route('users.index', ['search' => "O'Brien"]))->assertOk()
            ->assertInertia(fn ($page) => $page->where('users.data', fn ($rows): bool => collect($rows)->pluck('id')->all() === [$user->id]));
        asUser($this->admin)->get(route('users.index', ['search' => "'; DROP TABLE users; --"]))->assertOk()
            ->assertInertia(fn ($page) => $page->where('users.data', []));

        expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
    });

    test('show page exposes seat provenance and tenant on each duty pivot', function (): void {
        $user = makeUser($this->tenant);
        $source = $user->dutiables()->firstOrFail();
        $target = Duty::factory()->for($source->duty->institution)->create();
        $source->duty->exOfficioTargetDuties()->attach($target);
        $source->save();
        $seat = $source->derivedDutiables()->sole();
        $seat->update(['tenant_id' => $this->tenant->id]);

        asUser($this->admin)->get(route('users.show', $user))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowUser')
                ->where('user.current_duties', function ($duties) use ($source, $seat): bool {
                    $pivots = collect($duties)->pluck('pivot')->keyBy('id');

                    return $pivots[$seat->id]['via_dutiable_id'] === $source->id
                        && $pivots[$seat->id]['tenant_id'] === $this->tenant->id
                        && $pivots[$source->id]['via_dutiable_id'] === null;
                }));
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
            'name' => $user->name,
            'email' => 'updated@example.com',
            'phone' => '+370 987 6543',
        ];

        $response = asUser($this->admin)->put(route('users.update', $user), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => 'updated@example.com',
            'phone' => '+370 987 6543',
        ]);
    });

    test('rolls back duty changes when the user write fails', function (): void {
        $user = makeUser($this->tenant)->refresh();
        $dutiable = $user->dutiables()->sole();

        User::updating(fn (User $updating) => $updating->is($user)
            ? throw new RuntimeException('User write failed.')
            : null);
        $this->withoutExceptionHandling();

        expect(fn () => asUser($this->admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'phone' => '+370 987 6543',
            'current_duties' => [],
        ]))->toThrow(RuntimeException::class, 'User write failed.');

        expect($user->fresh()->getAttributes())->toEqual($user->getAttributes());
        expect($user->dutiables()->sole()->getAttributes())->toEqual($dutiable->getAttributes());
    });

    test('a former member without an email can still be saved', function (): void {
        $user = makeUser($this->tenant);
        $user->update(['email' => null]);

        asUser($this->admin)->put(route('users.update', $user), ['name' => $user->name, 'email' => '', 'phone' => '+370 600 00000'])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->phone)->toBe('+370 600 00000')
            ->and($user->fresh()->email)->toBeNull();
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
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
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
            'name' => $user->name,
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });
});

describe('relationships', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
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

        // Simulate a stale snapshot: the duty is passed as "new" even though an
        // active row already exists. The guard must keep it to a single row.
        SyncUserDuties::execute(
            $this->admin,
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

    test('removing a duty on the user form leaves a cross-tenant seat of that duty alone', function (): void {
        $target = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $otherTenant = Tenant::factory()->create();
        $duty->assignableTenants()->attach($otherTenant->id, ['quota' => 1]);

        $target->duties()->attach($duty->id, ['start_date' => now()->subMonth()]);
        $seat = Dutiable::factory()->create([
            'duty_id' => $duty->id, 'dutiable_id' => $target->id, 'dutiable_type' => MorphMap::alias(User::class),
            'tenant_id' => $otherTenant->id, 'start_date' => now()->subMonth(), 'end_date' => null,
        ]);

        SyncUserDuties::execute($this->admin, new Collection, new Collection([$duty->id]), $target, app(ModelAuthorizer::class), 'users.update.all');

        $homeRow = Dutiable::where('duty_id', $duty->id)->where('dutiable_id', $target->id)->whereNull('tenant_id')->sole();

        expect($homeRow->end_date->toDateString())->toBe(today()->subDay()->toDateString())
            ->and($seat->fresh()->end_date)->toBeNull();
    });

    test('a cross-tenant seat does not count as already holding the duty on the user form', function (): void {
        $target = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $otherTenant = Tenant::factory()->create();
        $duty->assignableTenants()->attach($otherTenant->id, ['quota' => 1]);
        Dutiable::factory()->create([
            'duty_id' => $duty->id, 'dutiable_id' => $target->id, 'dutiable_type' => MorphMap::alias(User::class),
            'tenant_id' => $otherTenant->id, 'start_date' => now()->subMonth(), 'end_date' => null,
        ]);

        SyncUserDuties::execute($this->admin, new Collection([$duty->id]), new Collection, $target, app(ModelAuthorizer::class), 'users.update.all');

        expect(Dutiable::where('duty_id', $duty->id)->where('dutiable_id', $target->id)->whereNull('tenant_id')->current()->count())->toBe(1);
    });

    test('removing and re-adding a duty on the same day does not overlap the two terms', function (): void {
        $target = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $target->duties()->attach($duty->id, ['start_date' => now()->subMonth()]);
        $sync = fn (array $now, array $before) => SyncUserDuties::execute($this->admin, new Collection($now), new Collection($before), $target, app(ModelAuthorizer::class), 'users.update.all');

        $sync([], [$duty->id]);
        $sync([$duty->id], []);

        $terms = Dutiable::where('duty_id', $duty->id)->where('dutiable_id', $target->id)->orderBy('start_date')->get();

        expect($terms)->toHaveCount(2)
            ->and($terms[1]->start_date->gt($terms[0]->end_date))->toBeTrue();
    });

    test('a term added on the user form starts after an institution closure ended the previous one', function (): void {
        $target = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $closed = Dutiable::factory()->create([
            'duty_id' => $duty->id, 'dutiable_id' => $target->id, 'dutiable_type' => MorphMap::alias(User::class),
            'start_date' => now()->subYear(), 'end_date' => null,
        ]);
        $closed->forceFill(['end_date' => today()->subDay(), 'ended_by_institution_deactivation' => true])->save();

        SyncUserDuties::execute($this->admin, new Collection([$duty->id]), new Collection, $target, app(ModelAuthorizer::class), 'users.update.all');

        $new = Dutiable::where('duty_id', $duty->id)->where('dutiable_id', $target->id)->whereKeyNot($closed->id)->sole();

        expect($new->start_date->toDateString())->toBe(today()->toDateString());
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

    test('roles sent on create are not given to the person', function (): void {
        $role = Role::firstOrCreate(['name' => 'Create Path Role', 'guard_name' => 'web']);

        asUser($this->admin)->post(route('users.store'), [
            'name' => 'Real Role',
            'email' => 'real.role@stud.vu.lt',
            'roles' => [$role->id],
            'current_duties' => [$this->duty->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(User::query()->where('email', 'real.role@stud.vu.lt')->firstOrFail()->roles)->toBeEmpty();
    });
});

describe('editing the person leaves their associations alone', function (): void {
    beforeEach(function (): void {
        $this->admin = makeAdminUser($this->tenant);
        $this->person = makeUser($this->tenant);
    });

    test('saving the form keeps the duties and the roles held directly', function (): void {
        $role = Role::firstOrCreate(['name' => 'Keeps Its Role', 'guard_name' => 'web']);
        $this->person->roles()->sync([$role->id]);
        $dutyIds = $this->person->current_duties()->pluck('duties.id')->all();

        asUser($this->admin)->patch(route('users.update', $this->person), [
            'name' => $this->person->name,
            'email' => $this->person->email,
            'phone' => '+37060000000',
            'roles' => [],
        ])->assertRedirect();

        $fresh = $this->person->fresh();

        expect($fresh->phone)->toBe('+37060000000')
            ->and($fresh->hasRole($role))->toBeTrue()
            ->and($fresh->current_duties()->pluck('duties.id')->all())->toEqualCanonicalizing($dutyIds);
    });
});

describe('super admin is a switch on the record', function (): void {
    beforeEach(function (): void {
        $this->admin = makeAdminUser($this->tenant);
        $this->person = makeUser($this->tenant);
        $this->superAdminRole = config('permission.super_admin_role_name');
    });

    test('a super admin grants and takes away super admin, leaving other roles alone', function (): void {
        $legacyRole = Role::firstOrCreate(['name' => 'Held Directly', 'guard_name' => 'web']);
        $this->person->assignRole($legacyRole);

        asUser($this->admin)->put(route('users.superAdmin.update', $this->person), ['is_super_admin' => true])
            ->assertRedirect();

        expect($this->person->fresh()->isSuperAdmin())->toBeTrue();

        asUser($this->admin)->put(route('users.superAdmin.update', $this->person), ['is_super_admin' => false])
            ->assertRedirect();

        expect($this->person->fresh()->isSuperAdmin())->toBeFalse()
            ->and($this->person->fresh()->hasRole($legacyRole))->toBeTrue();
    });

    test('the new super admin has full access on their next request', function (): void {
        expect(app(ModelAuthorizer::class)->allows($this->person, 'news.create.*'))->toBeFalse();

        asUser($this->admin)->put(route('users.superAdmin.update', $this->person), ['is_super_admin' => true]);

        expect(app(ModelAuthorizer::class)->allows($this->person->fresh(), 'news.create.*'))->toBeTrue();
    });

    test('anyone else is refused, even with permission to edit the person', function (): void {
        $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        asUser($coordinator)->put(route('users.superAdmin.update', $this->person), ['is_super_admin' => true])
            ->assertForbidden();

        expect($this->person->fresh()->isSuperAdmin())->toBeFalse();
    });

    test('a bare request changes nothing', function (): void {
        $this->person->assignRole($this->superAdminRole);

        asUser($this->admin)->put(route('users.superAdmin.update', $this->person), [])
            ->assertSessionHasErrors('is_super_admin');

        expect($this->person->fresh()->isSuperAdmin())->toBeTrue();
    });
});

describe('the record hands the assignment sheets their options', function (): void {
    test('upcoming terms include their saved timestamps', function (): void {
        $person = makeUser($this->tenant);
        $duty = Duty::factory()->create();
        $person->duties()->attach($duty, ['start_date' => today()->addWeek(), 'created_at' => now(), 'updated_at' => now()]);

        asUserWithInertia(makeAdminUser($this->tenant))->get(route('users.show', $person))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('user.upcoming_duties.0.pivot.created_at')
                ->has('user.upcoming_duties.0.pivot.updated_at'));
    });

    test('only to someone who may update the person, deferred, and never a role list', function (): void {
        $admin = makeAdminUser($this->tenant);
        $person = makeUser($this->tenant);

        asUserWithInertia($admin)->get(route('users.show', $person))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('can.updateSuperAdmin', true)
                ->where('isSuperAdmin', false)
                ->missing('assignment')
                ->loadDeferredProps('userPanels', fn ($panels) => $panels
                    ->has('assignment.studyPrograms')
                    ->missing('assignment.roles')));
    });

    test('a coordinator gets the programmes but cannot switch super admin', function (): void {
        $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $person = makeUser($this->tenant);

        asUserWithInertia($coordinator)->get(route('users.show', $person))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('can.updateSuperAdmin', false)
                ->loadDeferredProps('userPanels', fn ($panels) => $panels->has('assignment.studyPrograms')));
    });
});

test('user details batch task permissions for a padalinys coordinator', function (): void {
    $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $coordinator->duties()->firstOrFail()->givePermissionTo('tasks.update.padalinys');
    $user = makeUser($this->tenant);
    $addTasks = function (int $count) use ($user): void {
        for ($i = 0; $i < $count; $i++) {
            $institution = Institution::factory()->for($this->tenant)->create();
            Task::factory()->for($institution, 'taskable')->hasAttached($user, [], 'users')->create();
        }
    };
    $fetch = fn () => asUserWithInertia($coordinator)->getJson(route('users.show', $user))->assertOk();

    $measure = function (int $expectedTasks) use ($fetch): int {
        $fetch();
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $fetch();
            $queries = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $response->assertJsonMissingPath('props.user.tasks')
            ->assertJsonCount($expectedTasks, 'props.tasks')
            ->assertJsonPath('props.taskStats.total', $expectedTasks);

        expect(collect($response->json('props.tasks'))->pluck('can_update')->unique()->all())->toBe([true]);

        return $queries;
    };

    $addTasks(1);
    $small = $measure(1);
    $addTasks(4);

    expect($measure(5))->toBe($small);
});

test('user trash lists blocker reasons without adding queries per row', function (string $routeName, string $itemsPath): void {
    $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $institution = Institution::factory()->for($this->tenant)->create();
    $duty = Duty::factory()->for($institution)->create();
    $users = User::factory()->count(5)->hasAttached($duty, ['start_date' => now()->subDay()])->create();

    foreach ($users as $user) {
        Comment::factory()->for($user)->for($institution, 'commentable')->create();
        InstitutionCheckIn::factory()->for($institution)->create(['user_id' => $user->id]);
        Problem::factory()->for($this->tenant)->create(['created_by' => $user->id]);
        $user->delete();
    }

    $fetch = fn (int $perPage) => asUserWithInertia($coordinator)
        ->getJson(route($routeName, ['showDeleted' => 'true', 'per_page' => $perPage]))->assertOk();
    $measure = function (int $perPage) use ($fetch, $itemsPath): int {
        $fetch($perPage);
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $fetch($perPage);
            $queries = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $response->assertJsonCount($perPage, $itemsPath)
            ->assertJsonMissingPath($itemsPath.'.0.authored_comments_count')
            ->assertJsonMissingPath($itemsPath.'.0.institution_check_ins_count')
            ->assertJsonMissingPath($itemsPath.'.0.created_problems_count');
        $blockers = collect(['comments', 'check_ins', 'reported_problems'])
            ->map(fn (string $blocker): string => '1 '.trans_choice('trash.blockers.'.$blocker, 1))
            ->implode(', ');
        $reason = __('trash.blocked.generic', ['blockers' => $blockers]);
        expect(collect($response->json($itemsPath))->pluck('force_delete_blocked_reason')->unique()->all())->toBe([$reason]);

        return $queries;
    };

    $small = $measure(1);

    expect($measure(5))->toBe($small);
})->with([
    'page' => ['users.index', 'props.users.data'],
    'API' => ['api.v1.admin.users.index', 'data.items'],
]);

test('the user form directs delegated seat removals to the duty record', function (): void {
    $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $duty->assignableTenants()->attach($other, ['quota' => 1]);
    $seat = Dutiable::factory()->forDuty($duty)->forTenant($other)->active()->create();
    $user = $seat->user;

    asUser(makeAdminUser($this->tenant))->patch(route('users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'current_duties' => [],
    ])->assertSessionHasErrors(['current_duties' => __('users.delegated_duty_removal')]);

    expect($seat->fresh()->end_date)->toBeNull();
});

test('the user form places a closed institution assignment error on the duties field', function (): void {
    $duty = Duty::factory()->for(Institution::factory()->for($this->tenant)->state(['is_active' => false]))->create();
    $user = User::factory()->create();

    asUser(makeAdminUser($this->tenant))->patch(route('users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'current_duties' => [$duty->id],
    ])->assertSessionHasErrors(['current_duties' => __('forms.helpers.institution_assignment_closed')])
        ->assertSessionDoesntHaveErrors('end_date');

    expect($duty->dutiables()->exists())->toBeFalse();
});
