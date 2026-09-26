<?php

use App\Actions\GetFollowedInstitutions;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\InstitutionSubscriptionService;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first()
        ?? Tenant::factory()->create();

    $this->user = User::factory()->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->service = app(InstitutionSubscriptionService::class);
});

describe('InstitutionSubscriptionService', function (): void {
    test('followed institutions list includes the shared activity status', function (): void {
        $this->travelTo('2025-11-15');

        $this->institution->update(['meeting_periodicity_days' => 30]);
        Meeting::factory()
            ->hasAttached($this->institution)
            ->create(['start_time' => '2025-10-01 10:00:00']);
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        $followed = GetFollowedInstitutions::execute($this->user);

        expect($followed['total'])->toBe(1)
            ->and($followed['items'][0]['id'])->toBe($this->institution->id)
            ->and($followed['items'][0]['activity_status'])->toBe('overdue')
            ->and($followed['items'][0]['is_muted'])->toBeTrue();
    });

    test('followed institutions list is limited but counts them all', function (): void {
        Institution::factory()->for($this->tenant)->count(3)->create()
            ->each(fn (Institution $institution) => $this->service->follow($this->user, $institution));

        $followed = GetFollowedInstitutions::execute($this->user, 2);

        expect($followed['items'])->toHaveCount(2)
            ->and($followed['total'])->toBe(3);
    });

    test('user can follow an institution', function (): void {
        $this->service->follow($this->user, $this->institution);

        expect($this->user->follows($this->institution))->toBeTrue()
            ->and($this->user->followedInstitutions)->toHaveCount(1)
            ->and($this->user->followedInstitutions->first()->id)->toBe($this->institution->id);
    });

    test('user can unfollow an institution', function (): void {
        $this->service->follow($this->user, $this->institution);
        $this->service->unfollow($this->user, $this->institution);

        expect($this->user->follows($this->institution))->toBeFalse()
            ->and($this->user->followedInstitutions)->toBeEmpty();
    });

    test('user can mute an institution', function (): void {
        $this->service->mute($this->user, $this->institution);

        expect($this->user->isInstitutionMuted($this->institution))->toBeTrue()
            ->and($this->user->mutedInstitutions)->toHaveCount(1);
    });

    test('user can unmute an institution', function (): void {
        $this->service->mute($this->user, $this->institution);
        $this->service->unmute($this->user, $this->institution);

        expect($this->user->isInstitutionMuted($this->institution))->toBeFalse()
            ->and($this->user->mutedInstitutions)->toBeEmpty();
    });

    test('reset to defaults clears all mutes', function (): void {
        // Follow and mute some institutions
        $institution2 = Institution::factory()->for($this->tenant)->create();

        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);
        $this->service->follow($this->user, $institution2);
        $this->service->mute($this->user, $institution2);

        // Reset with clearFollows = false
        $this->service->resetToDefaults($this->user, clearFollows: false);

        expect($this->user->mutedInstitutions()->count())->toBe(0)
            ->and($this->user->followedInstitutions()->count())->toBe(2);
    });

    test('reset to defaults can clear follows too', function (): void {
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        // Reset with clearFollows = true
        $this->service->resetToDefaults($this->user, clearFollows: true);

        expect($this->user->mutedInstitutions()->count())->toBe(0)
            ->and($this->user->followedInstitutions()->count())->toBe(0);
    });

    test('get status returns correct values', function (): void {
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        $status = $this->service->getStatus($this->user, $this->institution);

        expect($status['is_followed'])->toBeTrue()
            ->and($status['is_muted'])->toBeTrue()
            ->and($status['is_duty_based'])->toBeFalse();
    });

    test('toggle follow works correctly', function (): void {
        // Not following initially
        expect($this->user->follows($this->institution))->toBeFalse();

        // Toggle on
        $result = $this->service->toggleFollow($this->user, $this->institution);
        expect($result)->toBeTrue()
            ->and($this->user->follows($this->institution))->toBeTrue();

        // Toggle off
        $result = $this->service->toggleFollow($this->user, $this->institution);
        expect($result)->toBeFalse()
            ->and($this->user->follows($this->institution))->toBeFalse();
    });

    test('toggle mute works correctly', function (): void {
        // Not muted initially
        expect($this->user->isInstitutionMuted($this->institution))->toBeFalse();

        // Toggle on
        $result = $this->service->toggleMute($this->user, $this->institution);
        expect($result)->toBeTrue()
            ->and($this->user->isInstitutionMuted($this->institution))->toBeTrue();

        // Toggle off
        $result = $this->service->toggleMute($this->user, $this->institution);
        expect($result)->toBeFalse()
            ->and($this->user->isInstitutionMuted($this->institution))->toBeFalse();
    });
});

describe('User follow/mute relationships', function (): void {
    test('institution can have multiple followers', function (): void {
        $user2 = User::factory()->create();

        $this->service->follow($this->user, $this->institution);
        $this->service->follow($user2, $this->institution);

        expect($this->institution->followers)->toHaveCount(2);
    });

    test('user can follow multiple institutions', function (): void {
        $institution2 = Institution::factory()->for($this->tenant)->create();

        $this->service->follow($this->user, $this->institution);
        $this->service->follow($this->user, $institution2);

        expect($this->user->followedInstitutions)->toHaveCount(2);
    });

    test('shouldNotifyForInstitution returns false when muted', function (): void {
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        expect($this->user->shouldNotifyForInstitution($this->institution))->toBeFalse();
    });

    test('shouldNotifyForInstitution returns true for followed institution', function (): void {
        $this->service->follow($this->user, $this->institution);

        expect($this->user->shouldNotifyForInstitution($this->institution))->toBeTrue();
    });

    test('shouldNotifyForInstitution returns false for unfollowed institution', function (): void {
        expect($this->user->shouldNotifyForInstitution($this->institution))->toBeFalse();
    });
});

describe('bulk follow API', function (): void {
    beforeEach(function (): void {
        $role = Role::firstOrCreate(['name' => 'Bulk Follow Reader', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'institutions.read.padalinys', 'guard_name' => 'web']));
        $this->reader = makeTenantUserWithRole($role->name, $this->tenant);
        $this->others = Institution::factory()->for($this->tenant)->count(2)->create();
    });

    test('follows every institution in one request and ignores ones already followed', function (): void {
        $this->service->follow($this->reader, $this->others[0]);

        asUser($this->reader)
            ->postJson(route('api.v1.admin.institutions.follows.store'), [
                'institution_ids' => $this->others->pluck('id')->all(),
            ])
            ->assertSuccessful();

        expect($this->reader->followedInstitutions()->pluck('institutions.id')->sort()->values()->all())
            ->toBe($this->others->pluck('id')->sort()->values()->all());
    });

    test('follows an active institution of another padalinys whose meetings are public', function (): void {
        $foreign = Institution::factory()->for(Tenant::factory())->create(['is_active' => 1]);
        $publicType = Type::factory()->create();
        app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$publicType->id]])->save();
        $foreign->types()->attach($publicType);

        asUser($this->reader)
            ->postJson(route('api.v1.admin.institutions.follows.store'), ['institution_ids' => [$foreign->id]])
            ->assertSuccessful();

        expect($this->reader->followedInstitutions()->pluck('institutions.id')->all())->toBe([$foreign->id]);
    });

    test('cannot follow another padalinys\' active institution whose meetings are not public', function (): void {
        $foreign = Institution::factory()->for(Tenant::factory())->create(['is_active' => 1]);

        asUser($this->reader)->postJson(route('api.v1.admin.institutions.follow', $foreign))->assertForbidden();

        expect($this->reader->followedInstitutions()->count())->toBe(0);
    });

    test('refuses the whole batch when one institution is not viewable', function (): void {
        $foreign = Institution::factory()->for(Tenant::factory())->create(['is_active' => 0]);

        asUser($this->reader)
            ->postJson(route('api.v1.admin.institutions.follows.store'), [
                'institution_ids' => [$this->others[0]->id, $foreign->id],
            ])
            ->assertForbidden();

        expect($this->reader->followedInstitutions()->count())->toBe(0);
    });

    test('unfollows only the user\'s own follows and clears their mutes', function (): void {
        $this->others->each(fn (Institution $institution) => $this->service->follow($this->reader, $institution));
        $this->service->mute($this->reader, $this->others[0]);
        $this->service->follow($this->user, $this->others[0]);

        asUser($this->reader)
            ->deleteJson(route('api.v1.admin.institutions.follows.destroy'), [
                'institution_ids' => $this->others->pluck('id')->all(),
            ])
            ->assertSuccessful();

        expect($this->reader->followedInstitutions()->count())->toBe(0)
            ->and($this->reader->mutedInstitutions()->count())->toBe(0)
            ->and($this->user->follows($this->others[0]))->toBeTrue();
    });
});
