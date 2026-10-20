<?php

use App\Enums\MailingListSourceType;
use App\Enums\ResponsibilityPurpose;
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

    test('lists responsibilities with their purpose and counts, never the super admin', function (): void {
        $this->role->givePermissionTo('news.update.padalinys');
        ResponsibilityGrant::factory()->forDuty($this->user->duties()->first())->of($this->role)->create();
        RoleFactory::forPurpose(ResponsibilityPurpose::StudentRepCoordination);

        asUser($this->admin)->get(route('responsibilities.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Permissions/IndexResponsibility')
                ->where('responsibilities', fn ($rows) => collect($rows)->doesntContain('title', config('permission.super_admin_role_name'))
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

        asUser($this->admin)->get(route('responsibilities.show', $this->role))
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
});

describe('grants', function (): void {
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
