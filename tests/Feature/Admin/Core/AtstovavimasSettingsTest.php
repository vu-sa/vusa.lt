<?php

use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\InstitutionType;
use App\Models\DutyType;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    // Clear any cached settings
    app()->forgetInstance(AtstovavimasSettings::class);
});

describe('student representative root type', function (): void {
    test('a super admin can update it and a regular user cannot', function (): void {
        $type = InstitutionType::query()->firstOrFail();

        asUser($this->user)->post(route('settings.atstovavimas.update'), [
            'student_rep_root_type_id' => $type->id,
        ])->assertForbidden();

        asUser($this->admin)->post(route('settings.atstovavimas.update'), [
            'student_rep_root_type_id' => $type->id,
        ])->assertRedirect();

        app()->forgetInstance(AtstovavimasSettings::class);

        expect(app(AtstovavimasSettings::class)->student_rep_root_type_id)->toBe($type->id);
    });

    test('an unknown root type is rejected', function (): void {
        asUser($this->admin)->post(route('settings.atstovavimas.update'), [
            'student_rep_root_type_id' => 999999,
        ])->assertSessionHasErrors('student_rep_root_type_id');
    });
});

describe('atstovavimas settings page access', function (): void {
    test('super admin can access atstovavimas settings page', function (): void {
        asUser($this->admin)
            ->get(route('settings.atstovavimas.edit'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/EditAtstovavimasSettings')
                ->has('student_rep_root_type_id')
            );
    });

    test('regular user cannot access atstovavimas settings page', function (): void {
        asUser($this->user)
            ->get(route('settings.atstovavimas.edit'))
            ->assertStatus(403);
    });
});

describe('permission-based tenant visibility', function (): void {
    test('user with institutions.read.padalinys permission sees tenant tab', function (): void {
        // Give user the permission via their duty's role
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $duty = $this->user->current_duties->first();
        $role = Role::firstOrCreate(['name' => 'Institution Reader Test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $duty->assignRole($role);

        asUser($this->user)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', function ($tenants) {
                    $collection = collect($tenants);

                    // User with permission should have available tenants
                    return $collection->isNotEmpty() &&
                           $collection->contains(fn ($t) => $t['id'] == $this->tenant->id);
                })
            );
    });

    test('user without read permission only sees assigned institutions', function (): void {
        // Create additional institution in the same tenant
        $extraInstitution = Institution::factory()->for($this->tenant)->create();

        // Regular user's assigned institution
        $userInstitutionId = $this->user->current_duties->first()->institution_id;

        asUser($this->user)
            ->get(route('dashboard.atstovavimas'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimas')
                ->where('userInstitutions', function ($institutions) use ($userInstitutionId, $extraInstitution) {
                    $collection = collect($institutions);

                    // Regular user should NOT see the extra institution (not assigned via duty)
                    return $collection->doesntContain(fn ($inst) => $inst['id'] == $extraInstitution->id) &&
                           $collection->contains(fn ($inst) => $inst['id'] == $userInstitutionId);
                })
                ->where('canViewTenantOverview', true)
            );
    });

    test('user with permission in one tenant only sees that tenant', function (): void {
        // Give user the permission via their duty's role
        $permission = Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']);
        $duty = $this->user->current_duties->first();
        $role = Role::firstOrCreate(['name' => 'Institution Reader Test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $duty->assignRole($role);

        // Create a second tenant with an institution
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $otherInstitution = Institution::factory()->for($otherTenant)->create();

        asUser($this->user)
            ->get(route('dashboard.atstovavimas.padaliniai'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowAtstovavimasPadaliniai')
                ->where('statsTenants', function ($tenants) use ($otherTenant) {
                    $collection = collect($tenants);

                    // Should not include the other tenant
                    return $collection->doesntContain(fn ($t) => $t['id'] == $otherTenant->id) &&
                           $collection->contains(fn ($t) => $t['id'] == $this->tenant->id);
                })
            );
    });
});

describe('translations', function (): void {
    test('every translation key referenced by the page resolves in lt and en', function (): void {
        $page = file_get_contents(resource_path('js/Pages/Admin/Settings/EditAtstovavimasSettings.vue'));
        preg_match_all("/\\\$t\('([a-z0-9_.]+)'/", $page, $matches);

        $keys = $matches[1];
        expect($keys)->not->toBeEmpty();

        foreach (['lt', 'en'] as $locale) {
            $translations = require lang_path("admin/{$locale}/settings.php");

            foreach ($keys as $key) {
                // Keys are prefixed with the file name ("settings."), Arr::get expects the path inside it.
                $path = substr($key, strlen('settings.'));
                expect(Arr::get($translations, $path))->not->toBeNull("Key [{$key}] missing in {$locale}");
            }
        }
    });
});
