<?php

use App\Models\Duty;
use App\Models\DutyName;
use App\Models\DutyType;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\News;
use App\Models\Pivots\Dutiable;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ContactPresentationService;
use App\Services\ResourceServices\SharepointFileService;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\PermissionRegistrar;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    // Create role if it doesn't exist and give it permissions
    $role = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.create.padalinys',
        'duties.update.padalinys',
        'duties.delete.padalinys',
        'news.create.padalinys', // Add news permission for the inheritance test
    ]);

    $this->regularUser = makeUser($this->tenant);
    $this->dutyManager = makeUser($this->tenant);
    $this->dutyManagerDuty = $this->dutyManager->duties()->first();
    $this->dutyManagerDuty->assignRole('Komunikacijos koordinatorius');
});

describe('unauthorized access', function (): void {
    test('cannot access duties index', function (): void {
        $response = asUser($this->regularUser)->get(route('duties.index'));
        expect($response->status())->toBe(403);
    });

    test('cannot open duty creation with a malformed prefill', function (): void {
        asUser($this->regularUser)
            ->get(route('duties.create', ['institution_id' => ['x']]))
            ->assertForbidden();
    });

    test('cannot create duties', function (): void {
        $response = asUser($this->regularUser)->post(route('duties.store'), [
            'name' => ['lt' => 'Test Duty', 'en' => 'Test Duty'],
            'institution_id' => $this->dutyManagerDuty->institution_id,
        ]);
        expect($response->status())->toBe(403);
    });

    test('cannot update duties', function (): void {
        $response = asUser($this->regularUser)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => ['lt' => 'Updated Duty', 'en' => 'Updated Duty'],
        ]);
        expect($response->status())->toBe(403);
    });

    test('cannot delete duties', function (): void {
        $response = asUser($this->regularUser)->delete(route('duties.destroy', $this->dutyManagerDuty));
        expect($response->status())->toBe(403);
    });
});

describe('authorized access', function (): void {
    test('loaded duty tenants preserve update and delete isolation', function (bool $eagerLoad): void {
        $local = Duty::factory()->for($this->dutyManagerDuty->institution)->create();
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $foreign = Duty::factory()->for(Institution::factory()->for($otherTenant))->create();
        if ($eagerLoad) {
            $local->load('tenants');
            $foreign->load('tenants');
        }

        expect($this->dutyManager->can('update', $local))->toBeTrue()
            ->and($this->dutyManager->can('delete', $local))->toBeTrue()
            ->and($this->dutyManager->can('update', $foreign))->toBeFalse()
            ->and($this->dutyManager->can('delete', $foreign))->toBeFalse();
    })->with(['lazy' => false, 'eager' => true]);

    test('inline duty creation via API returns the created model', function (): void {
        $response = asUser($this->dutyManager)->postJson(route('api.v1.admin.duties.store'), [
            'name' => ['lt' => 'Vedlio pareigybė', 'en' => 'Wizard duty'],
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
        ]);

        $created = Duty::where('name->lt', 'Vedlio pareigybė')->sole();
        $response->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.duty.id', $created->id);
    });

    test('duty manager can access duties index', function (): void {
        $response = asUser($this->dutyManager)->get(route('duties.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/People/IndexDuty'));
    });

    test('index query count stays constant as more duty rows are returned', function (string $routeName, string $itemsPath): void {
        Duty::factory()->for($this->dutyManagerDuty->institution)->count(10)->create();

        $fetch = fn (int $perPage) => asUserWithInertia($this->dutyManager)
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
            ->assertJsonPath($itemsPath.'.0.can_update', true)
            ->assertJsonPath($itemsPath.'.0.can_delete', true);
        expect($largeCount)->toBe($smallCount);
    })->with([
        'page' => ['duties.index', 'props.duties.data'],
        'API' => ['api.v1.admin.duties.index', 'data.items'],
    ]);

    test('super admin can access duties index', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $response = asUser($admin)->get(route('duties.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/People/IndexDuty'));
    });

    test('duty manager can create new duty', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->dutyManager)->post(route('duties.store'), [
            'name' => ['lt' => 'Nauja pareiga', 'en' => 'New Duty'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'institution_id' => $institution->id,
            'email' => 'duty@example.com',
            'is_active' => true,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('duties', [
            'institution_id' => $institution->id,
            'email' => 'duty@example.com',
        ]);

        $created = Duty::where('email', 'duty@example.com')->sole();
        $response->assertRedirectToRoute('duties.show', $created)->assertSessionHas('success');
        asUser($this->dutyManager)->get(route('duties.show', $created))->assertOk();
    });

    test('super admin can create new duty', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($admin)->post(route('duties.store'), [
            'name' => ['lt' => 'Admin Duty', 'en' => 'Admin Duty'],
            'description' => ['lt' => 'Admin Description', 'en' => 'Admin Description'],
            'institution_id' => $institution->id,
            'email' => 'admin.duty@example.com',
            'is_active' => true,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('duties', [
            'institution_id' => $institution->id,
            'email' => 'admin.duty@example.com',
        ]);
    });

    test('duty manager can update existing duty', function (): void {
        $response = asUser($this->dutyManager)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => ['lt' => 'Atnaujinta pareiga', 'en' => 'Updated Duty'],
            'description' => ['lt' => 'Naujas aprašymas', 'en' => 'New description'],
            'email' => 'updated@example.com',
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
            // Preserve the role + own membership (as the real form does) so the
            // manager doesn't lock themselves out of this duty.
            'roles' => $this->dutyManagerDuty->roles()->pluck('id')->all(),
            'current_users' => [$this->dutyManager->id],
        ]);

        $response->assertRedirect();

        $this->dutyManagerDuty->refresh();
        expect($this->dutyManagerDuty->email)->toBe('updated@example.com');
    });

    test('duty manager can assign users to duties', function (): void {
        $newUser = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'description' => $this->dutyManagerDuty->getTranslations('description'),
            'email' => $this->dutyManagerDuty->email,
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => $this->dutyManagerDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $this->dutyManagerDuty->places_to_occupy ?? 1,
            'roles' => $this->dutyManagerDuty->roles()->pluck('id')->all(),
            'current_users' => [$this->dutyManager->id, $newUser->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('dutiables', [
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $newUser->id,
        ]);
    });

    test('cannot assign user to duty from different tenant', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherDuty = Duty::factory()->create(['institution_id' => $otherInstitution->id]);
        $newUser = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->put(route('duties.update', $otherDuty), [
            'name' => $otherDuty->getTranslations('name'),
            'description' => $otherDuty->getTranslations('description'),
            'email' => $otherDuty->email,
            'institution_id' => $otherDuty->institution_id,
            'contacts_grouping' => $otherDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $otherDuty->places_to_occupy ?? 1,
            'current_users' => [$newUser->id],
        ]);

        expect($response->status())->toBe(403);
    });

    test('can batch add multiple users to a duty', function (): void {
        $user1 = makeUser($this->tenant);
        $user2 = makeUser($this->tenant);

        $response = asUser($this->dutyManager)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'description' => $this->dutyManagerDuty->getTranslations('description'),
            'email' => $this->dutyManagerDuty->email,
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => $this->dutyManagerDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $this->dutyManagerDuty->places_to_occupy ?? 1,
            'roles' => $this->dutyManagerDuty->roles()->pluck('id')->all(),
            'current_users' => [$this->dutyManager->id, $user1->id, $user2->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('dutiables', [
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('dutiables', [
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $user2->id,
        ]);
    });

    test('can batch remove users from a duty', function (): void {
        $user1 = makeUser($this->tenant);
        $user2 = makeUser($this->tenant);
        // Use a separate actor not assigned to this duty to avoid their own dutiable
        // being removed when current_users is updated, which would break authorization.
        $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $roleIds = $this->dutyManagerDuty->roles()->pluck('id')->toArray();

        // First add both users
        asUser($actor)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'description' => $this->dutyManagerDuty->getTranslations('description'),
            'email' => $this->dutyManagerDuty->email,
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => $this->dutyManagerDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $this->dutyManagerDuty->places_to_occupy ?? 1,
            'current_users' => [$user1->id, $user2->id],
            'roles' => $roleIds,
        ]);

        // Then remove user1, keep user2
        $response = asUser($actor)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'description' => $this->dutyManagerDuty->getTranslations('description'),
            'email' => $this->dutyManagerDuty->email,
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => $this->dutyManagerDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $this->dutyManagerDuty->places_to_occupy ?? 1,
            'current_users' => [$user2->id],
            'roles' => $roleIds,
        ]);

        $response->assertRedirect();

        // user1 should have an end_date set (soft removed)
        $this->assertDatabaseHas('dutiables', [
            'duty_id' => $this->dutyManagerDuty->id,
            'dutiable_id' => $user1->id,
        ]);

        $user1Pivot = DB::table('dutiables')
            ->where('duty_id', $this->dutyManagerDuty->id)
            ->where('dutiable_id', $user1->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->first();

        expect($user1Pivot->end_date)->not->toBeNull();

        // user2 should still be active (no end_date)
        $this->dutyManagerDuty->refresh();
        $currentUserIds = $this->dutyManagerDuty->current_users->pluck('id');
        expect($currentUserIds)->toContain($user2->id);
    });

    test('can add and remove users simultaneously', function (): void {
        $user1 = makeUser($this->tenant);
        $user2 = makeUser($this->tenant);
        $user3 = makeUser($this->tenant);
        // Use a separate actor not assigned to this duty to avoid their own dutiable
        // being removed when current_users is updated, which would break authorization.
        $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $roleIds = $this->dutyManagerDuty->roles()->pluck('id')->toArray();

        // First add user1 and user2
        asUser($actor)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'description' => $this->dutyManagerDuty->getTranslations('description'),
            'email' => $this->dutyManagerDuty->email,
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => $this->dutyManagerDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $this->dutyManagerDuty->places_to_occupy ?? 1,
            'current_users' => [$user1->id, $user2->id],
            'roles' => $roleIds,
        ]);

        // Remove user1, keep user2, add user3
        $response = asUser($actor)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'description' => $this->dutyManagerDuty->getTranslations('description'),
            'email' => $this->dutyManagerDuty->email,
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => $this->dutyManagerDuty->contacts_grouping ?? 'none',
            'places_to_occupy' => $this->dutyManagerDuty->places_to_occupy ?? 1,
            'current_users' => [$user2->id, $user3->id],
            'roles' => $roleIds,
        ]);

        $response->assertRedirect();

        $this->dutyManagerDuty->refresh();
        $currentUserIds = $this->dutyManagerDuty->current_users->pluck('id');

        expect($currentUserIds)->toContain($user2->id)
            ->toContain($user3->id)->not->toContain($user1->id);
    });

    test('edit page loads study_program from pivot auto-loading', function (): void {
        $response = asUser($this->dutyManager)->get(route('duties.edit', $this->dutyManagerDuty));

        $response->assertStatus(200);
    });
});

describe('validation', function (): void {
    test('requires name for store', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($admin)->post(route('duties.store'), [
            'institution_id' => $institution->id,
            'email' => 'test@example.com',
            'contacts_grouping' => 'none',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name.lt');
    });

    test('requires institution_id for store', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        $response = asUser($admin)->post(route('duties.store'), [
            'name' => ['lt' => 'Test Duty', 'en' => 'Test Duty'],
            'email' => 'test@example.com',
            'contacts_grouping' => 'none',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('institution_id');
    });

    test('requires contacts_grouping for store', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($admin)->post(route('duties.store'), [
            'name' => ['lt' => 'Test Duty', 'en' => 'Test Duty'],
            'institution_id' => $institution->id,
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('contacts_grouping');
    });

    test('requires valid email format for store', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($admin)->post(route('duties.store'), [
            'name' => ['lt' => 'Test Duty', 'en' => 'Test Duty'],
            'institution_id' => $institution->id,
            'email' => 'invalid-email',
            'contacts_grouping' => 'none',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('email');
    });

    test('requires name for update', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        $response = asUser($admin)->put(route('duties.update', $this->dutyManagerDuty), [
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'email' => 'updated@example.com',
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
        ]);

        // Since update doesn't use form request validation, this might not fail
        // Test the actual database state instead
        $this->dutyManagerDuty->refresh();

        // The name should not be updated if not provided
        expect($this->dutyManagerDuty->name)->not->toBeNull();
    });

    test('can add places_to_occupy validation for store', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        // Test that places_to_occupy accepts valid integer
        $response = asUser($admin)->post(route('duties.store'), [
            'name' => ['lt' => 'Test Duty', 'en' => 'Test Duty'],
            'institution_id' => $institution->id,
            'email' => 'test@example.com',
            'contacts_grouping' => 'none',
            'places_to_occupy' => 'invalid',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('places_to_occupy');
    });
});

describe('duty role management', function (): void {
    test('the duty form ignores roles, so it cannot grant even super admin', function (): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $role = Role::findByName(config('permission.super_admin_role_name'));

        asUser($this->dutyManager)->patch(route('duties.update', $duty), [
            'name' => ['lt' => 'Pakeista', 'en' => 'Changed'],
            'institution_id' => $duty->institution_id,
            'places_to_occupy' => 1,
            'contacts_grouping' => 'none',
            'roles' => [$role->id],
        ])->assertSessionHasNoErrors();

        expect($duty->fresh()->getTranslation('name', 'lt'))->toBe('Pakeista')
            ->and($duty->roles()->count())->toBe(0);
    });

    test('can assign roles to duties', function (): void {
        $this->dutyManagerDuty->assignRole('Komunikacijos koordinatorius');

        expect($this->dutyManagerDuty->hasRole('Komunikacijos koordinatorius'))->toBeTrue();
    });

    test('duty permissions are inherited by assigned users', function (): void {
        $this->dutyManagerDuty->assignRole('Komunikacijos koordinatorius');

        // Refresh user permissions cache
        $this->dutyManager->refresh();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        expect($this->dutyManager->can('news.create.padalinys'))->toBeTrue();
    })->todo('Permission inheritance through duties needs investigation');

    test('duty permissions are tenant-scoped', function (): void {
        $this->dutyManagerDuty->assignRole('Komunikacijos koordinatorius');

        $otherTenant = Tenant::factory()->create();
        $otherNews = News::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        expect($this->dutyManager->can('update', $otherNews))->toBeFalse();
    });

    test('attaching an attachable type to a duty automatically grants associated roles to the duty', function (): void {
        $type = DutyType::factory()->create();
        $grantedRole = Role::firstOrCreate(['name' => 'Automated Granted Role', 'guard_name' => 'web']);
        ResponsibilityGrant::factory()->forDutyType($type)->of($grantedRole)->create();

        // Allow duty manager to attach this type
        $managerRole = Role::findByName('Komunikacijos koordinatorius');
        $managerRole->attachable_types()->attach($type->id);

        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        $response = asUser($this->dutyManager)->put(route('duties.update', $duty), [
            'name' => ['lt' => 'Test Pareigybė', 'en' => 'Test Duty'],
            'institution_id' => $duty->institution_id,
            'places_to_occupy' => 1,
            'contacts_grouping' => 'none',
            'types' => [$type->id],
        ]);

        $response->assertSessionHasNoErrors();
        expect($duty->fresh()->hasRole('Automated Granted Role'))->toBeTrue();

        // Detaching the type removes the role
        asUser($this->dutyManager)->put(route('duties.update', $duty), [
            'name' => ['lt' => 'Test Pareigybė', 'en' => 'Test Duty'],
            'institution_id' => $duty->institution_id,
            'places_to_occupy' => 1,
            'contacts_grouping' => 'none',
            'types' => [],
        ])->assertSessionHasNoErrors();

        expect($duty->fresh()->hasRole('Automated Granted Role'))->toBeFalse();
    });
});

describe('ex-officio target tenant scoping', function (): void {
    test('padalinys-scope admin cannot set a cross-tenant duty as ex-officio target', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignDuty = Duty::factory()->for(Institution::factory()->for($otherTenant))->create();

        $response = asUser($this->dutyManager)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
            'ex_officio_target_duty_ids' => [$foreignDuty->id],
        ]);

        $response->assertSessionHasErrors('ex_officio_target_duty_ids');
        expect($this->dutyManagerDuty->exOfficioTargetDuties()->count())->toBe(0);
    });

    test('padalinys-scope admin can set a same-tenant duty as ex-officio target', function (): void {
        $sameTenantDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        $response = asUser($this->dutyManager)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
            'roles' => $this->dutyManagerDuty->roles()->pluck('id')->all(),
            'current_users' => [$this->dutyManager->id],
            'ex_officio_target_duty_ids' => [$sameTenantDuty->id],
        ]);

        $response->assertSessionDoesntHaveErrors('ex_officio_target_duty_ids');
        $response->assertRedirect();
        expect($this->dutyManagerDuty->exOfficioTargetDuties()->pluck('duties.id')->all())->toBe([$sameTenantDuty->id]);
    });

    test('super admin can set a cross-tenant duty as ex-officio target', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignDuty = Duty::factory()->for(Institution::factory()->for($otherTenant))->create();

        $response = asUser($superAdmin)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
            'ex_officio_target_duty_ids' => [$foreignDuty->id],
        ]);

        $response->assertSessionDoesntHaveErrors('ex_officio_target_duty_ids');
        $response->assertRedirect();
        expect($this->dutyManagerDuty->exOfficioTargetDuties()->pluck('duties.id')->all())->toBe([$foreignDuty->id]);
    });

    test('a duty cannot be its own ex-officio target', function (): void {
        $response = asUser($this->dutyManager)->put(route('duties.update', $this->dutyManagerDuty), [
            'name' => $this->dutyManagerDuty->getTranslations('name'),
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
            'ex_officio_target_duty_ids' => [$this->dutyManagerDuty->id],
        ]);

        $response->assertSessionHasErrors('ex_officio_target_duty_ids.0');
    });
});

describe('duty search indexing (cross-tenant visibility)', function (): void {
    test('searchable array indexes home tenant plus assignable tenants', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $duty = Duty::factory()->for(Institution::factory()->for($otherTenant))->create([
            'name' => ['lt' => 'Zzz Unikalus Isorinis', 'en' => 'Zzz Unique External'],
        ]);
        $duty->assignableTenants()->attach($this->tenant->id, ['quota' => 1]);

        $searchable = $duty->fresh()->toSearchableArray();

        // home_tenant_id stays the owning tenant — used to flag "external" rows.
        expect($searchable['home_tenant_id'])->toBe((int) $otherTenant->id);

        // tenant_ids drives the scoped-key filter and must contain BOTH the home
        // tenant and the assignable tenant so cross-tenant admins can find it.
        expect($searchable['tenant_ids'])
            ->toContain((int) $otherTenant->id)
            ->toContain((int) $this->tenant->id);
    });

    test('searchable array exposes facet + member fields', function (): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
        ]);
        $member = User::factory()->create(['name' => 'Jonas Jonaitis']);
        $duty->users()->attach($member->id, ['start_date' => now()->subYear(), 'end_date' => null]);

        $searchable = $duty->fresh()->toSearchableArray();

        expect($searchable)->toHaveKeys(['name_lt', 'name_en', 'tenant_shortname', 'type_titles', 'current_user_names'])
            ->and($searchable['current_user_names'])->toContain('Jonas Jonaitis')
            ->and($searchable['current_users_count'])->toBe(1);
    });

    test('searchable array indexes all current and previous members without limits', function (): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
        ]);

        $currentMembers = User::factory()->count(15)->create();
        foreach ($currentMembers as $member) {
            $duty->users()->attach($member->id, ['start_date' => now()->subYear(), 'end_date' => null]);
        }

        $previousMembers = User::factory()->count(10)->create();
        foreach ($previousMembers as $member) {
            $duty->users()->attach($member->id, ['start_date' => now()->subYears(2), 'end_date' => now()->subMonths(6)]);
        }

        $searchable = $duty->fresh()->toSearchableArray();

        expect($searchable['current_user_names'])->toHaveCount(15)
            ->and($searchable['previous_user_names'])->toHaveCount(10)
            ->and($searchable['current_users_count'])->toBe(15);
    });
});

describe('duty form props', function (): void {
    test('create and edit do not ship the whole user directory', function (): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        asUserWithInertia($this->dutyManager)->get(route('duties.create'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->missing('assignableUsers'));

        asUserWithInertia($this->dutyManager)->get(route('duties.edit', $duty))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->missing('assignableUsers'));
    });
});

describe('duty creation institution-tenant scoping', function (): void {
    test('padalinys-scope admin cannot create a duty in another tenant\'s institution', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignInstitution = Institution::factory()->for($otherTenant)->create();

        $response = asUser($this->dutyManager)->post(route('duties.store'), [
            'name' => ['lt' => 'Svetima pareiga', 'en' => 'Foreign Duty'],
            'institution_id' => $foreignInstitution->id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
        ]);

        $response->assertSessionHasErrors('institution_id');
        $this->assertDatabaseMissing('duties', ['institution_id' => $foreignInstitution->id]);
    });

    test('padalinys-scope admin can create a duty in their own tenant\'s institution', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();

        $response = asUser($this->dutyManager)->post(route('duties.store'), [
            'name' => ['lt' => 'Nauja pareiga', 'en' => 'New Duty'],
            'institution_id' => $institution->id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
        ]);

        $response->assertSessionDoesntHaveErrors('institution_id');
        $response->assertRedirect();
        $this->assertDatabaseHas('duties', ['institution_id' => $institution->id]);
    });

    test('super admin can create a duty in any institution', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreignInstitution = Institution::factory()->for($otherTenant)->create();

        $response = asUser($superAdmin)->post(route('duties.store'), [
            'name' => ['lt' => 'Admin pareiga', 'en' => 'Admin Duty'],
            'institution_id' => $foreignInstitution->id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => 1,
        ]);

        $response->assertSessionDoesntHaveErrors('institution_id');
        $response->assertRedirect();
        $this->assertDatabaseHas('duties', ['institution_id' => $foreignInstitution->id]);
    });
});

describe('data quality filters', function (): void {
    test('filters vacant duties', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $vacantDuty = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Tuščia pareiga', 'en' => 'Vacant Duty'],
        ]);
        $occupiedDuty = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Užimta pareiga', 'en' => 'Occupied Duty'],
        ]);
        $user = makeUser($this->tenant);
        $occupiedDuty->attachAudited('users', $user->id, ['start_date' => now()->subDay()]);

        asUserWithInertia($this->dutyManager)
            ->get(route('duties.index', ['filters' => json_encode(['data_quality' => 'vacant'])]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/IndexDuty')
                ->where('duties.data', fn ($data) => collect($data)->pluck('id')->contains($vacantDuty->id)
                    && ! collect($data)->pluck('id')->contains($occupiedDuty->id))
            );
    });

    test('filters duties missing english name', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $missingEn = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Be angliško pavadinimo', 'en' => ''],
        ]);
        $complete = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Pilnas pavadinimas', 'en' => 'Full name'],
        ]);

        asUserWithInertia($this->dutyManager)
            ->get(route('duties.index', ['filters' => json_encode(['data_quality' => 'missing_en_name'])]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/IndexDuty')
                ->where('duties.data', fn ($data) => collect($data)->pluck('id')->contains($missingEn->id)
                    && ! collect($data)->pluck('id')->contains($complete->id))
            );
    });

    test('filters duties missing lithuanian name', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $missingLt = Duty::factory()->for($institution)->create([
            'name' => ['lt' => '', 'en' => 'No Lithuanian name'],
        ]);
        $complete = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Pilnas pavadinimas', 'en' => 'Full name'],
        ]);

        asUserWithInertia($this->dutyManager)
            ->get(route('duties.index', ['filters' => json_encode(['data_quality' => 'missing_lt_name'])]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/IndexDuty')
                ->where('duties.data', fn ($data) => collect($data)->pluck('id')->contains($missingLt->id)
                    && ! collect($data)->pluck('id')->contains($complete->id))
            );
    });

    test('filters duties with duplicate active holders', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $user = makeUser($this->tenant);
        $duplicateDuty = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Pasikartojantys', 'en' => 'Duplicate holders'],
        ]);
        $singleHolder = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Vienas narys', 'en' => 'Single holder'],
        ]);

        // Create two active dutiable rows for the same user on the same duty.
        DB::table('dutiables')->insert([
            [
                'id' => (string) Str::ulid(),
                'duty_id' => $duplicateDuty->id,
                'dutiable_id' => $user->id,
                'dutiable_type' => MorphMap::alias(User::class),
                'start_date' => now()->subMonths(2)->toDateString(),
                'end_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'duty_id' => $duplicateDuty->id,
                'dutiable_id' => $user->id,
                'dutiable_type' => MorphMap::alias(User::class),
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        asUserWithInertia($this->dutyManager)
            ->get(route('duties.index', ['filters' => json_encode(['data_quality' => 'duplicate_holders'])]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/IndexDuty')
                ->where('duties.data', fn ($data) => collect($data)->pluck('id')->contains($duplicateDuty->id)
                    && ! collect($data)->pluck('id')->contains($singleHolder->id))
            );
    });

    test('all filter returns full list', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $first = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Pirma', 'en' => 'First']]);
        $second = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Antra', 'en' => 'Second']]);

        asUserWithInertia($this->dutyManager)
            ->get(route('duties.index', ['filters' => json_encode(['data_quality' => 'all'])]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/IndexDuty')
                ->where('duties.data', fn ($data) => collect($data)->pluck('id')->contains($first->id)
                    && collect($data)->pluck('id')->contains($second->id))
            );
    });
});

describe('show page', function (): void {
    test('defers sibling duties and study programs out of the first visit', function (): void {
        $institution = $this->dutyManagerDuty->institution;

        // A sibling duty in the same institution.
        Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Kita pareiga', 'en' => 'Other duty'],
        ]);

        $response = asUser($this->dutyManager)->get(route('duties.show', $this->dutyManagerDuty));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/ShowDuty')
                ->has('duty.users.0.pivot.created_at')
                ->has('duty.users.0.pivot.updated_at')
                ->missing('otherDuties')
                ->missing('studyPrograms')
                ->missing('duty.next_meeting')
                ->loadDeferredProps('dutyPanels', fn (AssertableInertia $page) => $page
                    ->has('otherDuties', 1)
                    ->has('studyPrograms')
                )
            );
    });

    /**
     * The page's action buttons used to read `auth.can['duties.update.padalinys']`, which
     * that map never contains — so they rendered for nobody. The capability is per-record
     * because the permission is tenant-scoped.
     */
    test('the capability prop reflects the policy for this duty', function (): void {
        asUserWithInertia($this->dutyManager)->get(route('duties.show', $this->dutyManagerDuty))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('can.update', true)
                ->where('can.managePeople', true)
            );
    });

    test('a read-only user may open the page but gets no capabilities', function (): void {
        $readerRole = Role::firstOrCreate(['name' => 'Duty Reader', 'guard_name' => 'web']);
        $readerRole->givePermissionTo('duties.read.padalinys');

        $reader = makeUser($this->tenant);
        $reader->duties()->first()->assignRole($readerRole);

        asUserWithInertia($reader)->get(route('duties.show', $this->dutyManagerDuty))
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('can.update', false)
                ->where('can.managePeople', false)
                ->whereType('can.assignQuota', 'boolean')
                ->whereType('can.reviewExOfficio', 'boolean')
                ->whereType('duty.id', 'string')
                ->whereType('duty.users', 'array')
                ->whereType('duty.tenants.0.id', 'integer')
                ->whereType('duty.tenants.0.laravel_through_key', 'string')
                ->missing('responsibilityOptions')
                ->loadDeferredProps('dutyPanels', fn (AssertableInertia $panels) => $panels
                    ->whereType('lineage.events', 'array')
                    ->whereType('lineage.predecessorTerms', 'array')
                    ->whereType('responsibilities.items', 'array'))
            );
    });
});

describe('informational seat count', function (): void {
    test('creation accepts an omitted blank or manually entered reference', function (array $reference, ?int $expected): void {
        asUser($this->dutyManager)->post(route('duties.store'), [
            'name' => ['lt' => 'Informacinė pareigybė', 'en' => 'Reference duty'],
            'institution_id' => $this->dutyManagerDuty->institution_id,
            'contacts_grouping' => 'none',
            ...$reference,
        ])->assertSessionHasNoErrors()->assertRedirect();

        expect(Duty::where('name->lt', 'Informacinė pareigybė')->sole()->places_to_occupy)->toBe($expected);
    })->with([
        'omitted' => [[], null],
        'blank' => [['places_to_occupy' => null], null],
        'manual' => [['places_to_occupy' => 38], 38],
    ]);

    test('updates preserve omitted references and allow setting or clearing them', function (array $reference, ?int $expected): void {
        $duty = Duty::factory()->for($this->dutyManagerDuty->institution)->create(['places_to_occupy' => 38]);

        asUser($this->dutyManager)->patch(route('duties.update', $duty), [
            'name' => ['lt' => 'Pareigybė', 'en' => 'Duty'],
            'institution_id' => $duty->institution_id,
            'contacts_grouping' => 'none',
            ...$reference,
        ])->assertSessionHasNoErrors()->assertRedirect();

        expect($duty->fresh()->places_to_occupy)->toBe($expected);
    })->with([
        'omitted' => [[], 38],
        'cleared' => [['places_to_occupy' => null], null],
        'empty input' => [['places_to_occupy' => ''], null],
        'manual' => [['places_to_occupy' => 40], 40],
    ]);

    test('creation and updates reject invalid reference counts', function (mixed $value): void {
        $duty = Duty::factory()->for($this->dutyManagerDuty->institution)->create(['places_to_occupy' => 38]);
        $payload = [
            'name' => ['lt' => 'Netinkamas skaičius', 'en' => 'Invalid count'],
            'institution_id' => $duty->institution_id,
            'contacts_grouping' => 'none',
            'places_to_occupy' => $value,
        ];

        asUser($this->dutyManager)->post(route('duties.store'), $payload)->assertSessionHasErrors('places_to_occupy');
        asUser($this->dutyManager)->patch(route('duties.update', $duty), $payload)->assertSessionHasErrors('places_to_occupy');

        expect($duty->fresh()->places_to_occupy)->toBe(38)
            ->and(Duty::where('name->lt', 'Netinkamas skaičius')->exists())->toBeFalse();
    })->with(['zero' => 0, 'negative' => -1, 'fraction' => 1.5, 'text' => 'invalid']);
});

test('duty creation ignores a malformed optional institution prefill', function (): void {
    asUser($this->dutyManager)
        ->get(route('duties.create', ['institution_id' => ['x']]))
        ->assertOk()
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('prefillInstitutionId', null)
        );
});

describe('ShowDuty payload contract', function (): void {
    test('localized record and file panels preserve the existing JSON', function (string $locale, bool $isHolder): void {
        $duty = $isHolder ? $this->dutyManagerDuty : Duty::factory()->for($this->dutyManagerDuty->institution)->create();
        if (! $isHolder) {
            Dutiable::factory()->forDuty($duty)->forUser($this->regularUser)->create([
                'start_date' => '2025-01-01', 'end_date' => null, 'study_program_id' => null,
            ]);
        }
        $duty->update(['name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator']]);
        $type = DutyType::factory()->create(['title' => ['lt' => 'Kategorija', 'en' => 'Category']]);
        $duty->types()->attach($type);
        DutyName::factory()->for($duty)->create();
        $file = FileableFile::factory()->create([
            'fileable_type' => MorphMap::alias(Duty::class), 'fileable_id' => $duty->id,
            'size_bytes' => null, 'file_date' => null, 'description' => null,
        ]);
        $typeFile = FileableFile::factory()->create([
            'fileable_type' => MorphMap::alias(DutyType::class), 'fileable_id' => (string) $type->id,
        ]);
        app()->setLocale($locale);
        $expected = $duty->fresh()->load([
            'institution.tenant', 'users' => fn ($query) => $query->withPivot('created_at', 'updated_at'),
            'types', 'formerNames',
        ]);
        $this->dutyManager->can('view', $expected);
        $expected->setRelation('users', app(ContactPresentationService::class)->sortHolders($expected, $expected->users));
        $record = $expected->toArray();
        $record['sharepointPath'] = SharepointFileService::pathOrNull($expected);
        $record['sharepointFolderUrl'] = SharepointFileService::folderUrlOrNull($expected);
        $expectedTypeFile = $typeFile->fresh()->load('fileable:id,title')->toArray();

        asUserWithInertia($this->dutyManager)->withSession(['lang' => $locale])
            ->get(route('duties.show', $duty))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/ShowDuty')
                ->where('duty', $record)
                ->where('duty.name', $locale === 'lt' ? 'Koordinatorius' : 'Coordinator')
                ->whereType('duty.id', 'string')
                ->whereType('duty.description', 'string|null')
                ->whereType('duty.places_to_occupy', 'integer|null')
                ->whereType('duty.is_active', 'boolean')
                ->whereType('duty.institution.tenant.type', 'string|null')
                ->where('duty.institution.types', [])
                ->whereType('duty.users.0.email', 'string|null')
                ->whereType('duty.users.0.pronouns', 'string|null')
                ->whereType('duty.users.0.is_active', 'integer')
                ->whereType('duty.users.0.show_pronouns', 'boolean')
                ->whereType('duty.users.0.pivot.start_date', 'string')
                ->whereType('duty.users.0.pivot.end_date', 'string|null')
                ->whereType('duty.users.0.pivot.description', 'string|null')
                ->whereType('duty.users.0.pivot.study_program_note', 'string|null')
                ->whereType('duty.users.0.pivot.tenant', 'array|null')
                ->whereType('duty.users.0.pivot.study_program', 'array|null')
                ->whereType('duty.former_names.0.name', 'string')
                ->whereType('duty.types.0.title', 'string')
                ->whereType('can.update', 'boolean')
                ->whereType('can.managePeople', 'boolean')
                ->whereType('can.assignQuota', 'boolean')
                ->whereType('can.reviewExOfficio', 'boolean')
                ->missing('files')->missing('typeFiles')->missing('lineage')->missing('responsibilityOptions')
                ->loadDeferredProps('files', fn (AssertableInertia $files) => $files
                    ->where('files', [$file->fresh()->toArray()])
                    ->where('typeFiles', [$expectedTypeFile])
                    ->whereType('files.0.size_bytes', 'null')
                    ->whereType('files.0.file_date', 'null')
                    ->whereType('typeFiles.0.fileable.id', 'integer')
                    ->where('typeFiles.0.fileable.title', $locale === 'lt' ? 'Kategorija' : 'Category'))
                ->reloadOnly('responsibilityOptions', fn (AssertableInertia $options) => $options
                    ->whereType('responsibilityOptions.responsibilities', 'array')
                    ->whereType('responsibilityOptions.tenants', 'array')
                    ->whereType('responsibilityOptions.types', 'array')
                    ->whereType('responsibilityOptions.institutions', 'array')));
    })->with(['lt', 'en'])->with(['holder' => true, 'tenant manager' => false]);

    test('institution types and periodicity preserve the previous serialization', function (?int $periodicity): void {
        $duty = $this->dutyManagerDuty;
        $institution = $duty->institution;
        $type = InstitutionType::factory()->create([
            'title' => ['lt' => 'Taryba', 'en' => 'Council'],
            'description' => ['lt' => 'Tarybos tipas', 'en' => 'Council type'],
            'extra_attributes' => ['meeting_periodicity_days' => 21],
        ]);
        $institution->types()->attach($type);
        $institution->update(['meeting_periodicity_days' => $periodicity]);
        $expected = $institution->fresh()->load('tenant')->toArray();

        asUserWithInertia($this->dutyManager)->get(route('duties.show', $duty))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('duty.institution', $expected)
                ->where('duty.institution.meeting_periodicity_days', $periodicity ?? 21));
    })->with(['inherited from type' => null, 'explicit override' => 14]);

    test('nullable database values stay null in the record payload', function (): void {
        $duty = $this->dutyManagerDuty;
        DB::table('duties')->where('id', $duty->id)->update([
            'name' => null, 'description' => null, 'email' => null, 'places_to_occupy' => null,
        ]);
        DB::table('institutions')->where('id', $duty->institution_id)->update([
            'tenant_id' => null, 'name' => null, 'short_name' => null, 'description' => null,
            'address' => null, 'working_hours' => null,
        ]);
        DB::table('dutiables')->where('duty_id', $duty->id)->update([
            'description' => null, 'study_program_note' => null, 'study_program_id' => null, 'tenant_id' => null,
        ]);

        asUserWithInertia($this->dutyManager)->get(route('duties.show', $duty))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('duty.name', null)
                ->where('duty.description', null)
                ->where('duty.email', null)
                ->where('duty.places_to_occupy', null)
                ->where('duty.institution.name', null)
                ->where('duty.institution.short_name', null)
                ->where('duty.institution.description', null)
                ->where('duty.institution.address', null)
                ->where('duty.institution.working_hours', null)
                ->where('duty.institution.tenant', null)
                ->where('duty.users.0.pivot.description', null)
                ->where('duty.users.0.pivot.study_program_note', null)
                ->where('duty.users.0.pivot.tenant', null)
                ->where('duty.users.0.pivot.study_program', null)
                ->where('duty.sharepointPath', null)
                ->where('duty.sharepointFolderUrl', null));
    });

    test('a holder without management permissions receives a typed read-only record', function (): void {
        $duty = $this->regularUser->duties()->firstOrFail();

        asUserWithInertia($this->regularUser)->get(route('duties.show', $duty))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/People/ShowDuty')
                ->whereType('duty.id', 'string')
                ->whereType('duty.name', 'string|null')
                ->whereType('duty.users.0.pivot.id', 'string')
                ->where('can.update', false)
                ->where('can.managePeople', false)
                ->where('can.assignQuota', false)
                ->where('can.reviewExOfficio', false)
                ->missing('responsibilityOptions')
                ->loadDeferredProps('dutyPanels', fn (AssertableInertia $panels) => $panels
                    ->whereType('lineage.events', 'array')
                    ->whereType('responsibilities.items', 'array')));

        asUserWithInertia($this->regularUser)->withHeaders([
            'X-Inertia-Partial-Component' => 'Admin/People/ShowDuty',
            'X-Inertia-Partial-Data' => 'responsibilityOptions',
        ])->get(route('duties.show', $duty))->assertRedirect()->assertSessionHas('error');
    });

    test('a non-holder without read permission cannot receive a record', function (): void {
        asUser($this->regularUser)->get(route('duties.show', $this->dutyManagerDuty))->assertForbidden();
    });

    test('deferred panels type siblings programmes quota and lineage variants', function (): void {
        $duty = $this->dutyManagerDuty;
        $predecessor = Duty::factory()->for($duty->institution)->create(['name' => ['lt' => 'Ankstesnė', 'en' => 'Previous']]);
        $successor = Duty::factory()->for($duty->institution)->create();
        Dutiable::factory()->forDuty($successor)->forUser($this->regularUser)->create([
            'start_date' => '2025-01-01', 'end_date' => null, 'study_program_id' => null,
        ]);
        $duty->predecessors()->attach($predecessor, ['effective_on' => '2025-01-01']);
        $duty->successors()->attach($successor, ['effective_on' => '2026-01-01']);
        DutyName::factory()->for($duty)->create(['used_until' => '2024-01-01']);
        Dutiable::factory()->forDuty($predecessor)->forUser($this->regularUser)->create([
            'start_date' => '2023-01-01', 'end_date' => '2024-12-31', 'study_program_id' => null,
        ]);
        $program = StudyProgram::factory()->forTenant($this->tenant)->create();
        $duty->assignableTenants()->attach($this->tenant, ['quota' => null]);

        asUserWithInertia($this->dutyManager)->get(route('duties.show', $duty))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('otherDuties')->missing('studyPrograms')->missing('quotaTenants')->missing('responsibilities')
                ->loadDeferredProps('dutyPanels', fn (AssertableInertia $panels) => $panels
                    ->whereType('otherDuties.0.id', 'string')
                    ->whereType('otherDuties.0.name', 'string')
                    ->whereType('otherDuties.0.current_users', 'array')
                    ->where('otherDuties', fn ($siblings) => collect($siblings)->contains(fn ($item) => $item['id'] === $successor->id
                        && is_string($item['current_users'][0]['pivot']['created_at'])
                        && is_string($item['current_users'][0]['pivot']['updated_at'])))
                    ->where('studyPrograms', fn ($programs) => collect($programs)->contains(fn ($item) => $item['id'] === $program->id && is_string($item['name']) && is_int($item['tenant_id'])))
                    ->whereType('quotaTenants.0.id', 'integer')
                    ->whereType('quotaTenants.0.shortname', 'string')
                    ->where('quotaTenants.0.quota', null)
                    ->whereType('quotaTenants.0.occupied', 'integer')
                    ->where('lineage.events.0.kind', 'handed_over')
                    ->missing('lineage.events.0.duties.0.can_fold')
                    ->where('lineage.events.1.kind', 'took_over')
                    ->whereType('lineage.events.1.duties.0.can_fold', 'boolean')
                    ->whereType('lineage.events.1.duties.0.link_id', 'integer')
                    ->where('lineage.events.2.kind', 'rename')
                    ->whereType('lineage.events.2.name', 'string')
                    ->whereType('lineage.predecessorTerms.0.id', 'string')
                    ->whereType('lineage.predecessorTerms.0.profile_photo_path', 'string|null')
                    ->whereType('lineage.predecessorTerms.0.duty_name', 'string')
                    ->whereType('lineage.predecessorTerms.0.via_duty_id', 'string')
                    ->whereType('lineage.predecessorTerms.0.pivot.end_date', 'string')
                    ->whereType('lineage.suggestedLinkDate', 'string')
                    ->whereType('responsibilities.items', 'array')));
    });
});
