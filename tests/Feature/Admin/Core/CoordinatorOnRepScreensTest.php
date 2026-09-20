<?php

use App\Actions\GetInstitutionCoordinator;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $role = Role::factory()->create(['guard_name' => 'web']);
    $settings = app(AtstovavimasSettings::class);
    $settings->setInstitutionManagerRoleId($role->id);
    $settings->save();

    $duty = Duty::factory()->for($this->institution)->create(['name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator']]);
    $duty->roles()->attach($role);
    $this->coordinator = User::factory()->create(['name' => 'Ona Koordinatorė']);
    $this->coordinator->duties()->attach($duty, ['start_date' => now()->subMonth(), 'end_date' => null]);
});

describe('GetInstitutionCoordinator', function (): void {
    test('names the koordinatorius with their duty', function (): void {
        expect(GetInstitutionCoordinator::execute($this->institution))
            ->toMatchArray(['name' => 'Ona Koordinatorė', 'duty' => 'Koordinatorius']);
    });

    test('is never the person asking', function (): void {
        expect(GetInstitutionCoordinator::execute($this->institution, $this->coordinator))->toBeNull();
    });

    test('is null when the tenant has no coordinator', function (): void {
        expect(GetInstitutionCoordinator::execute(Institution::factory()->for(Tenant::factory()->create())->create()))->toBeNull();
    });
});

describe('the coordinator is one tap away on rep screens (R-g)', function (): void {
    test('the ViSAK overview carries it, deferred', function (): void {
        $rep = makeTenantUserWithRole('Student Representative', $this->tenant);

        asUser($rep)->get(route('dashboard.atstovavimas'))->assertInertia(fn (Assert $page) => $page
            ->missing('coordinator')
            ->loadDeferredProps('secondary', fn (Assert $page) => $page->where('coordinator.name', 'Ona Koordinatorė'))
        );
    });

    test('the meeting record names its own institution\'s coordinator, deferred', function (): void {
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();

        asUser(makeAdminUser())->get(route('meetings.show', $meeting))->assertInertia(fn (Assert $page) => $page
            ->missing('coordinator')
            ->loadDeferredProps('meetingPanels', fn (Assert $page) => $page->where('coordinator.name', 'Ona Koordinatorė'))
        );
    });
});
