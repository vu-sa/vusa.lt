<?php

use App\Enums\MailingListSourceType;
use App\Enums\ResponsibilityPurpose;
use App\Models\Activity;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\MailingListSource;
use App\Models\Permission;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use Database\Factories\RoleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);
    $this->role = Role::factory()->create(['title' => ['lt' => 'Komunikacija', 'en' => 'Communication']]);
});

describe('collection', function (): void {
    test('a member without responsibility permissions is refused', function (): void {
        asUser($this->user)->get(route('responsibilities.index'))->assertForbidden();
    });

    test('lists responsibilities with their purpose and counts, the super admin included', function (): void {
        $this->role->givePermissionTo('news.update.padalinys');
        ResponsibilityGrant::factory()->forDuty($this->user->duties()->first())->of($this->role)->create();
        RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination);

        asUserWithInertia($this->admin)->get(route('responsibilities.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/IndexResponsibility')
                ->where('responsibilities', fn ($rows) => collect($rows)->contains('id', Role::findByName(config('permission.super_admin_role_name'))->id)
                    && collect($rows)->firstWhere('id', $this->role->id)['people_count'] === 1
                    && collect($rows)->firstWhere('id', $this->role->id)['permissions_count'] === 1
                    && collect($rows)->firstWhere('purpose', 'student_rep_coordination') !== null));
    });

    test('old role links redirect to responsibilities', function (): void {
        asUser($this->admin)->get('/mano/roles')->assertRedirect('/mano/responsibilities');
        asUser($this->admin)->get("/mano/roles/{$this->role->id}")->assertRedirect("/mano/responsibilities/{$this->role->id}");
    });
});

describe('record', function (): void {
    test('offers the whole catalog and defers grants and people', function (): void {
        $legacy = Permission::create(['name' => 'saziningaiExams.read.padalinys', 'guard_name' => 'web']);
        $this->role->givePermissionTo(['mailingLists.read.padalinys', $legacy]);

        asUserWithInertia($this->admin)->get(route('responsibilities.show', $this->role))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/ShowResponsibility')
                ->where('responsibility.title.lt', 'Komunikacija')
                ->where('catalog.resources', fn ($resources) => collect($resources)->contains('key', 'mailingLists'))
                ->where('grantedPermissions', ['mailingLists.read.padalinys'])
                ->where('legacyPermissions', ['saziningaiExams.read.padalinys'])
                ->missing('grants')
                ->missing('people')
                ->loadDeferredProps(['grants', 'people'], fn (Assert $deferred) => $deferred
                    ->has('grants.duties')
                    ->has('people')));
    });
});

describe('create and edit', function (): void {
    test('a new responsibility gets its key from the Lithuanian title', function (): void {
        asUser($this->admin)->post(route('responsibilities.store'), [
            'title' => ['lt' => 'Organizacijos valdymas', 'en' => 'Running the organisation'],
            'description' => ['lt' => 'Prezidentės darbas.'],
        ])->assertRedirect();

        $created = Role::query()->where('name', 'organizacijos-valdymas')->firstOrFail();

        expect($created->getTranslation('title', 'en'))->toBe('Running the organisation')
            ->and($created->getTranslation('description', 'lt'))->toBe('Prezidentės darbas.');
    });

    test('the title is required and the key cannot be typed', function (): void {
        asUser($this->admin)->post(route('responsibilities.store'), ['title' => ['en' => 'Only English'], 'name' => 'x'])
            ->assertSessionHasErrors(['title.lt', 'name']);
    });

    test('editing changes the title, never the key', function (): void {
        $name = $this->role->name;

        asUser($this->admin)->patch(route('responsibilities.update', $this->role), [
            'title' => ['lt' => 'Komunikacija · padalinys', 'en' => 'Communication'],
        ])->assertRedirect(route('responsibilities.show', $this->role));

        expect($this->role->fresh()->name)->toBe($name)
            ->and($this->role->fresh()->displayTitle('lt'))->toBe('Komunikacija · padalinys');
    });
});

describe('permissions', function (): void {
    test('saves the whole set, keeping permissions outside the catalog', function (): void {
        $legacy = Permission::create(['name' => 'saziningaiExams.read.padalinys', 'guard_name' => 'web']);
        $this->role->givePermissionTo(['news.update.padalinys', $legacy]);

        asUser($this->admin)
            ->put(route('responsibilities.permissions.update', $this->role), ['permissions' => ['mailingLists.update.padalinys', 'news.update.*']])
            ->assertSessionHasNoErrors();

        expect($this->role->fresh()->permissions->pluck('name')->sort()->values()->all())
            ->toBe(['mailingLists.update.padalinys', 'news.update.*', 'saziningaiExams.read.padalinys']);
    });

    test('refuses baseline, unknown and super admin edits', function (): void {
        asUser($this->admin)
            ->put(route('responsibilities.permissions.update', $this->role), ['permissions' => ['problems.read.*', 'news.fly.*']])
            ->assertSessionHasErrors(['permissions.0', 'permissions.1']);

        asUser($this->admin)
            ->put(route('responsibilities.permissions.update', Role::findByName(config('permission.super_admin_role_name'))), ['permissions' => []])
            ->assertForbidden();
    });

    test('a manager cannot take away their own way back', function (): void {
        $managing = Role::factory()->create();
        $managing->givePermissionTo(['roles.read.*', 'roles.update.*']);
        $manager = makeUser($this->tenant);
        $manager->duties()->first()->assignRole($managing);

        asUser($manager)
            ->put(route('responsibilities.permissions.update', $managing), ['permissions' => ['roles.read.*']])
            ->assertSessionHasErrors('permissions');

        expect($managing->fresh()->hasPermissionTo('roles.update.*'))->toBeTrue();
    });
    test('a responsibility given to a duty type refuses Everywhere and permanent-deletion permissions', function (): void {
        ResponsibilityGrant::factory()->forDutyType(DutyType::factory()->create())->of($this->role)->create();

        foreach (['news.update.*', 'news.forceDelete.padalinys'] as $wide) {
            asUser($this->admin)
                ->put(route('responsibilities.permissions.update', $this->role), ['permissions' => [$wide]])
                ->assertSessionHasErrors('permissions')
                ->assertSessionDoesntHaveErrors('permissions.0');
        }

        expect($this->role->fresh()->permissions)->toBeEmpty();

        asUser($this->admin)
            ->put(route('responsibilities.permissions.update', $this->role), ['permissions' => ['news.update.padalinys', 'news.delete.padalinys']])
            ->assertSessionHasNoErrors();
    });
});

describe('grants', function (): void {
    test('a duty type is refused a responsibility with Everywhere or permanent-deletion permissions; a duty is not', function (): void {
        $this->role->givePermissionTo('news.update.*');
        $type = DutyType::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), ['grantee_type' => 'duty_type', 'grantee_id' => (string) $type->id])
            ->assertSessionHasErrors('grantee_id');
        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), ['grantee_type' => 'duty', 'grantee_id' => $duty->id])
            ->assertSessionHasNoErrors();

        expect($type->responsibilityGrants()->exists())->toBeFalse()
            ->and($duty->roles()->pluck('roles.id')->all())->toBe([$this->role->id]);
    });

    test('gives the responsibility to a duty and to a duty type, and takes it away', function (): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $type = DutyType::factory()->create();
        $typed = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $typed->types()->attach($type);

        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), ['grantee_type' => 'duty', 'grantee_id' => $duty->id])
            ->assertSessionHasNoErrors();
        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), ['grantee_type' => 'duty_type', 'grantee_id' => (string) $type->id])
            ->assertSessionHasNoErrors();

        expect($duty->roles()->pluck('roles.id')->all())->toBe([$this->role->id])
            ->and($typed->roles()->pluck('roles.id')->all())->toBe([$this->role->id]);

        asUser($this->admin)->delete(route('responsibilities.grants.destroy', [$this->role, $type->responsibilityGrants()->firstOrFail()]));

        expect($typed->roles()->exists())->toBeFalse();
    });

    test('a purpose with scopes needs one; a plain responsibility refuses one', function (): void {
        $coordination = RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        asUser($this->admin)->post(route('responsibilities.grants.store', $coordination), ['grantee_type' => 'duty', 'grantee_id' => $duty->id])
            ->assertSessionHasErrors(['scope_type', 'scope_id']);
        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), [
            'grantee_type' => 'duty', 'grantee_id' => $duty->id, 'scope_type' => 'tenant', 'scope_id' => (string) $this->tenant->id,
        ])->assertSessionHasErrors(['scope_type', 'scope_id']);
    });

    test('a duty type may cover each duty\'s own padalinys, a single duty may not', function (): void {
        $registration = RoleFactory::forPurpose(ResponsibilityPurpose::MemberRegistration);
        $type = DutyType::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        asUser($this->admin)->post(route('responsibilities.grants.store', $registration), ['grantee_type' => 'duty', 'grantee_id' => $duty->id, 'scope_type' => 'own_tenant'])
            ->assertSessionHasErrors('scope_type');
        asUser($this->admin)->post(route('responsibilities.grants.store', $registration), ['grantee_type' => 'duty_type', 'grantee_id' => (string) $type->id, 'scope_type' => 'own_tenant'])
            ->assertSessionHasNoErrors();

        expect($type->responsibilityGrants()->firstOrFail()->only('scope_type', 'scope_id'))->toBe(['scope_type' => 'tenant', 'scope_id' => null]);
    });

    test('an institution covers only its own padalinys', function (): void {
        $registration = RoleFactory::forPurpose(ResponsibilityPurpose::MemberRegistration);
        $institution = Institution::factory()->for($this->tenant)->create();

        asUser($this->admin)->post(route('responsibilities.grants.store', $registration), [
            'grantee_type' => 'institution', 'grantee_id' => $institution->id, 'scope_type' => 'tenant', 'scope_id' => (string) $this->tenant->id,
        ])->assertSessionHasErrors('scope_type');
        asUser($this->admin)->post(route('responsibilities.grants.store', $registration), ['grantee_type' => 'institution', 'grantee_id' => $institution->id, 'scope_type' => 'own_tenant'])
            ->assertSessionHasNoErrors();
    });

    test('a grant id from another responsibility returns 404', function (): void {
        $grant = ResponsibilityGrant::factory()->forDuty($this->user->duties()->first())->of(Role::factory()->create())->create();

        asUser($this->admin)->delete(route('responsibilities.grants.destroy', [$this->role, $grant]))->assertNotFound();
    });

    test('saves which duty types holders may assign', function (): void {
        $type = DutyType::factory()->create();

        asUser($this->admin)->put(route('responsibilities.attachableTypes.update', $this->role), ['attachable_types' => [$type->id]])
            ->assertSessionHasNoErrors();

        expect($this->role->attachable_types()->pluck('duty_types.id')->all())->toBe([$type->id]);
    });
});

describe('people holding a responsibility directly', function (): void {
    beforeEach(function (): void {
        $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->first();
        $this->holder = makeUser($this->tenant);
        $this->holder->duties()->attach(Duty::factory()->for(Institution::factory()->for($this->otherTenant))->create(), ['start_date' => now()->subDay()]);
        $this->holder->assignRole($this->role);
    });

    test('are listed under Kam suteikta with every padalinys the role reaches through their duties', function (): void {
        asUser($this->admin)->get(route('responsibilities.show', $this->role))
            ->assertInertia(fn (Assert $page) => $page
                ->where('facts.users', 1)
                ->where('can.removeUsers', true)
                ->loadDeferredProps('grants', fn (Assert $deferred) => $deferred
                    ->where('grants.users.0.id', $this->holder->id)
                    ->where('grants.users.0.tenants', fn ($tenants) => collect($tenants)->sort()->values()->all() === collect([__($this->tenant->shortname), __($this->otherTenant->shortname)])->sort()->values()->all())));
    });

    test('the super admin responsibility lists the super admins', function (): void {
        asUser($this->admin)->get(route('responsibilities.show', Role::findByName(config('permission.super_admin_role_name'))))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('responsibility.is_super_admin', true)
                ->loadDeferredProps('grants', fn (Assert $deferred) => $deferred
                    ->where('grants.users', fn ($users) => collect($users)->contains('id', $this->admin->id))));
    });

    test('a super admin takes it away', function (): void {
        asUser($this->admin)->delete(route('responsibilities.users.destroy', [$this->role, $this->holder]))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($this->holder->fresh()->hasRole($this->role))->toBeFalse();
    });

    test('a responsibility manager who is not a super admin may not', function (): void {
        $managing = Role::factory()->create();
        $managing->givePermissionTo(['roles.read.*', 'roles.update.*']);
        $manager = makeUser($this->tenant);
        $manager->duties()->first()->assignRole($managing);

        asUser($manager)->get(route('responsibilities.show', $this->role))
            ->assertInertia(fn (Assert $page) => $page->where('can.removeUsers', false));

        asUser($manager)->delete(route('responsibilities.users.destroy', [$this->role, $this->holder]))->assertForbidden();

        expect($this->holder->fresh()->hasRole($this->role))->toBeTrue();
    });

    test('someone who does not hold it returns 404', function (): void {
        asUser($this->admin)->delete(route('responsibilities.users.destroy', [$this->role, $this->user]))->assertNotFound();
    });
});

describe('deleting', function (): void {
    test('takes the responsibility off every duty it reached', function (): void {
        $duty = $this->user->duties()->first();
        $duty->assignRole($this->role);

        asUser($this->admin)->delete(route('responsibilities.destroy', $this->role))->assertRedirect(route('responsibilities.index'));

        expect(Role::query()->whereKey($this->role->id)->exists())->toBeFalse()
            ->and($duty->roles()->exists())->toBeFalse();
    });

    test('refuses while the app or a mailing list depends on it', function (): void {
        $purposeful = RoleFactory::forPurpose(ResponsibilityPurpose::MemberRegistration);
        $list = MailingList::factory()->create();
        MailingListSource::factory()->for($list)->create(['type' => MailingListSourceType::Responsibility, 'source_id' => $this->role->id]);

        asUser($this->admin)->delete(route('responsibilities.destroy', $purposeful))->assertSessionHas('error');
        asUser($this->admin)->delete(route('responsibilities.destroy', $this->role))->assertSessionHas('error');
        asUser($this->admin)->delete(route('responsibilities.destroy', Role::findByName(config('permission.super_admin_role_name'))))->assertSessionHas('error');

        expect(Role::query()->whereKey([$purposeful->id, $this->role->id])->count())->toBe(2);
    });
});

test('a member without permissions can reach none of it', function (): void {
    asUser($this->user)->get(route('responsibilities.show', $this->role))->assertForbidden();
    asUser($this->user)->get(route('responsibilities.create'))->assertForbidden();
    asUser($this->user)->post(route('responsibilities.store'), ['title' => ['lt' => 'X']])->assertForbidden();
    asUser($this->user)->get(route('responsibilities.edit', $this->role))->assertForbidden();
    asUser($this->user)->patch(route('responsibilities.update', $this->role), ['title' => ['lt' => 'X']])->assertForbidden();
    asUser($this->user)->delete(route('responsibilities.destroy', $this->role))->assertForbidden();
    asUser($this->user)->put(route('responsibilities.permissions.update', $this->role), ['permissions' => []])->assertForbidden();
    asUser($this->user)->post(route('responsibilities.grants.store', $this->role), ['grantee_type' => 'duty', 'grantee_id' => 'x'])->assertForbidden();
});

describe('responsibility history', function (): void {
    test('title and description edits log raw locale changes on the role with the actor', function (): void {
        asUser($this->admin)->patch(route('responsibilities.update', $this->role), [
            'title' => ['lt' => 'Naujas pavadinimas', 'en' => 'New title'],
            'description' => ['lt' => 'Padėk kolegoms.', 'en' => 'Help colleagues.'],
        ])->assertSessionHasNoErrors();

        $entry = Activity::forSubject($this->role)->forEvent('updated')->sole();
        expect($entry->causer_id)->toBe($this->admin->id)
            ->and(json_decode($entry->attribute_changes->get('attributes')['title'], true))->toBe(['lt' => 'Naujas pavadinimas', 'en' => 'New title'])
            ->and(json_decode($entry->attribute_changes->get('attributes')['description'], true))->toBe(['lt' => 'Padėk kolegoms.', 'en' => 'Help colleagues.'])
            ->and(json_decode($entry->attribute_changes->get('old')['title'], true))->toBe(['lt' => 'Komunikacija', 'en' => 'Communication']);

        asUser($this->admin)->patch(route('responsibilities.update', $this->role), [
            'title' => ['lt' => 'Naujas pavadinimas', 'en' => 'New title'],
            'description' => ['lt' => 'Padėk kolegoms.', 'en' => 'Help colleagues.'],
        ])->assertSessionHasNoErrors();

        expect(Activity::forSubject($this->role)->forEvent('updated')->count())->toBe(1);
    });

    test('permission changes log actual added and removed names once and unchanged saves log nothing', function (): void {
        $this->role->givePermissionTo('news.update.padalinys');
        $before = Activity::forSubject($this->role)->count();

        asUser($this->admin)->put(route('responsibilities.permissions.update', $this->role), ['permissions' => ['news.read.padalinys']])
            ->assertSessionHasNoErrors();

        $entry = Activity::forSubject($this->role)->forEvent('relation_updated')->sole();
        expect($entry->causer_id)->toBe($this->admin->id)
            ->and($entry->properties->get('relation'))->toBe('permissions')
            ->and($entry->properties->get('attached'))->toBe([['id' => (string) Permission::findByName('news.read.padalinys')->id, 'label' => 'news.read.padalinys']])
            ->and($entry->properties->get('detached'))->toBe([['id' => (string) Permission::findByName('news.update.padalinys')->id, 'label' => 'news.update.padalinys']])
            ->and(Activity::forSubject($this->role)->count())->toBe($before + 1);

        asUser($this->admin)->put(route('responsibilities.permissions.update', $this->role), ['permissions' => ['news.read.padalinys']])
            ->assertSessionHasNoErrors();
        expect(Activity::forSubject($this->role)->count())->toBe($before + 1);
    });

    test('grant changes through the responsibility log their target and scope on the role once', function (string $type): void {
        $target = match ($type) {
            'duty' => $this->user->duties()->first(),
            'duty_type' => DutyType::factory()->create(),
            'institution' => Institution::factory()->for($this->tenant)->create(),
        };
        $data = ['grantee_type' => $type, 'grantee_id' => (string) $target->id];
        $before = Activity::forSubject($this->role)->count();

        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), $data)->assertSessionHasNoErrors();
        $grant = $this->role->grants()->sole();
        $entry = Activity::forSubject($this->role)->forEvent('relation_updated')->sole();
        expect($entry->causer_id)->toBe($this->admin->id)
            ->and($entry->properties->get('relation'))->toBe('grants')
            ->and($entry->properties->get('attached')[0]['id'])->toBe($grant->id)
            ->and($entry->properties->get('attached')[0]['label'])->toHaveKeys(['lt', 'en'])
            ->and($entry->properties->get('grant'))->toBe($grant->only(['id', 'grantee_type', 'grantee_id', 'scope_type', 'scope_id']))
            ->and(Activity::forSubject($this->role)->count())->toBe($before + 1);

        asUser($this->admin)->post(route('responsibilities.grants.store', $this->role), $data)->assertSessionHasNoErrors();
        expect(Activity::forSubject($this->role)->count())->toBe($before + 1);

        asUser($this->admin)->delete(route('responsibilities.grants.destroy', [$this->role, $grant]))->assertRedirect();
        $removed = Activity::forSubject($this->role)->latest('id')->firstOrFail();
        expect($removed->causer_id)->toBe($this->admin->id)
            ->and($removed->properties->get('detached'))->toBe($entry->properties->get('attached'))
            ->and($removed->properties->get('attached'))->toBe([])
            ->and(Activity::forSubject($this->role)->count())->toBe($before + 2);
    })->with(['duty', 'duty_type', 'institution']);

    test('removing a direct holder logs the person on the role', function (): void {
        $this->user->assignRole($this->role);

        asUser($this->admin)->delete(route('responsibilities.users.destroy', [$this->role, $this->user]))->assertRedirect();

        $entry = Activity::forSubject($this->role)->forEvent('relation_updated')->sole();
        expect($entry->causer_id)->toBe($this->admin->id)
            ->and($entry->properties->all())->toBe([
                'relation' => 'users',
                'attached' => [],
                'detached' => [['id' => $this->user->id, 'label' => $this->user->name]],
            ]);
    });

    test('attachable type sync logs additions and removals and skips unchanged saves', function (): void {
        $old = DutyType::factory()->create();
        $new = DutyType::factory()->create();
        $this->role->attachable_types()->attach($old);

        asUser($this->admin)->put(route('responsibilities.attachableTypes.update', $this->role), ['attachable_types' => [$new->id]])
            ->assertSessionHasNoErrors();

        $entry = Activity::forSubject($this->role)->forEvent('relation_updated')->sole();
        expect($entry->causer_id)->toBe($this->admin->id)
            ->and($entry->properties->get('relation'))->toBe('attachable_types')
            ->and($entry->properties->get('attached')[0]['id'])->toBe((string) $new->id)
            ->and($entry->properties->get('detached')[0]['id'])->toBe((string) $old->id);

        asUser($this->admin)->put(route('responsibilities.attachableTypes.update', $this->role), ['attachable_types' => [$new->id]])
            ->assertSessionHasNoErrors();
        expect(Activity::forSubject($this->role)->forEvent('relation_updated')->count())->toBe(1);
    });
});
