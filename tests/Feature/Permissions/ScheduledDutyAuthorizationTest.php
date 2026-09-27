<?php

use App\Enums\Responsibility;
use App\Events\DutiableChanged;
use App\Listeners\HandleDutiableChange;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\CapabilitySnapshot;
use App\Services\ResponsibilityResolver;
use App\Settings\FormSettings;
use App\Settings\SettingsSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();
    $this->actor = User::factory()->create();

    $this->role = Role::firstOrCreate(['name' => 'Scheduled Duty Manager', 'guard_name' => 'web']);
    $this->role->givePermissionTo(Permission::firstOrCreate(['name' => 'institutions.read.own', 'guard_name' => 'web']));
    $this->role->givePermissionTo(Permission::firstOrCreate(['name' => 'news.update.padalinys', 'guard_name' => 'web']));
    $this->duty->assignRole($this->role);

    $this->term = Dutiable::factory()->forDuty($this->duty)->forUser($this->actor)->create([
        'start_date' => now()->addMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]);
});

test('a scheduled duty grants its role and own and tenant scopes without making the holder current', function (): void {
    $authorizer = app(ModelAuthorizer::class);
    $otherInstitution = Institution::factory()->for($this->otherTenant)->create();

    expect($this->actor->fresh()->current_duties()->exists())->toBeFalse()
        ->and($this->duty->fresh()->current_users()->exists())->toBeFalse()
        ->and($authorizer->allows($this->actor, 'news.update.padalinys'))->toBeTrue()
        ->and($authorizer->tenants($this->actor, 'news.update.padalinys')->pluck('id'))->toContain($this->tenant->id)
        ->not->toContain($this->otherTenant->id)
        ->and(Gate::forUser($this->actor)->allows('view', $this->institution))->toBeTrue()
        ->and(Gate::forUser($this->actor)->allows('view', $otherInstitution))->toBeFalse()
        ->and(app(InstitutionAccessService::class)->getUserDutyInstitutionIds($this->actor))->toContain($this->institution->id)
        ->and(CapabilitySnapshot::capture($this->actor)->roles)->toContain($this->role->name);

    Meeting::factory()->hasAttached($this->institution)->create(['start_time' => now()->addWeeks(6)]);

    asUser($this->actor)->get(route('dashboard.atstovavimas'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('user.authorization_duties.0.id', $this->duty->id)
            ->where('userInstitutions.0.id', $this->institution->id)
            ->where('upcomingMeetings.total', 1)
        );
});

test('ending the only scheduled duty revokes access and clears every per-user access cache', function (): void {
    $authorizer = app(ModelAuthorizer::class);
    expect($authorizer->allows($this->actor, 'news.update.padalinys'))->toBeTrue();

    Cache::put(InstitutionAccessService::getAccessCacheKey($this->actor->id), collect([$this->institution->id]));
    Cache::put('typesense_scoped_keys:'.$this->actor->id, ['stale' => true]);

    $this->term->update(['end_date' => now()->subDay()->toDateString()]);
    app(HandleDutiableChange::class)->handle(new DutiableChanged($this->term));

    expect($authorizer->allows($this->actor, 'news.update.padalinys'))->toBeFalse()
        ->and(Cache::get(InstitutionAccessService::getAccessCacheKey($this->actor->id)))->toBeNull()
        ->and(Cache::get('typesense_scoped_keys:'.$this->actor->id))->toBeNull();
});

test('scheduled duty roles work in internal role-based access checks', function (): void {
    DutyResponsibility::factory()->for($this->duty)->forTenant($this->tenant)->create();
    $formSettings = app(FormSettings::class);
    $formSettings->member_registration_notification_recipient_role_id = (string) $this->role->id;
    $formSettings->save();

    $settings = app(SettingsSettings::class);
    $settings->settings_manager_role_id = (string) $this->role->id;
    $settings->save();

    expect($formSettings->userIsMemberRegistrationRecipient($this->actor))->toBeTrue()
        ->and($formSettings->getMemberRegistrationTenantIds($this->actor))->toContain($this->tenant->id)
        ->and(app(ResponsibilityResolver::class)->holdsAnywhere($this->actor, Responsibility::StudentRepCoordination))->toBeFalse()
        ->and($settings->canUserManageSettings($this->actor))->toBeTrue();
});
