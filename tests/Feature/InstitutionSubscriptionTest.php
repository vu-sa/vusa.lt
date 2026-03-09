<?php

use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstitutionSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first()
        ?? Tenant::factory()->create();

    $this->user = User::factory()->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->service = app(InstitutionSubscriptionService::class);
});

describe('InstitutionSubscriptionService', function () {
    test('user can follow an institution', function () {
        $this->service->follow($this->user, $this->institution);

        expect($this->user->follows($this->institution))->toBeTrue()
            ->and($this->user->followedInstitutions)->toHaveCount(1)
            ->and($this->user->followedInstitutions->first()->id)->toBe($this->institution->id);
    });

    test('user can unfollow an institution', function () {
        $this->service->follow($this->user, $this->institution);
        $this->service->unfollow($this->user, $this->institution);

        expect($this->user->follows($this->institution))->toBeFalse()
            ->and($this->user->followedInstitutions)->toHaveCount(0);
    });

    test('user can mute an institution', function () {
        $this->service->mute($this->user, $this->institution);

        expect($this->user->isInstitutionMuted($this->institution))->toBeTrue()
            ->and($this->user->mutedInstitutions)->toHaveCount(1);
    });

    test('user can unmute an institution', function () {
        $this->service->mute($this->user, $this->institution);
        $this->service->unmute($this->user, $this->institution);

        expect($this->user->isInstitutionMuted($this->institution))->toBeFalse()
            ->and($this->user->mutedInstitutions)->toHaveCount(0);
    });

    test('reset to defaults clears all mutes', function () {
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

    test('reset to defaults can clear follows too', function () {
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        // Reset with clearFollows = true
        $this->service->resetToDefaults($this->user, clearFollows: true);

        expect($this->user->mutedInstitutions()->count())->toBe(0)
            ->and($this->user->followedInstitutions()->count())->toBe(0);
    });

    test('get status returns correct values', function () {
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        $status = $this->service->getStatus($this->user, $this->institution);

        expect($status['is_followed'])->toBeTrue()
            ->and($status['is_muted'])->toBeTrue()
            ->and($status['is_duty_based'])->toBeFalse();
    });

    test('toggle follow works correctly', function () {
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

    test('toggle mute works correctly', function () {
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

describe('User follow/mute relationships', function () {
    test('institution can have multiple followers', function () {
        $user2 = User::factory()->create();

        $this->service->follow($this->user, $this->institution);
        $this->service->follow($user2, $this->institution);

        expect($this->institution->followers)->toHaveCount(2);
    });

    test('user can follow multiple institutions', function () {
        $institution2 = Institution::factory()->for($this->tenant)->create();

        $this->service->follow($this->user, $this->institution);
        $this->service->follow($this->user, $institution2);

        expect($this->user->followedInstitutions)->toHaveCount(2);
    });

    test('shouldNotifyForInstitution returns false when muted', function () {
        $this->service->follow($this->user, $this->institution);
        $this->service->mute($this->user, $this->institution);

        expect($this->user->shouldNotifyForInstitution($this->institution))->toBeFalse();
    });

    test('shouldNotifyForInstitution returns true for followed institution', function () {
        $this->service->follow($this->user, $this->institution);

        expect($this->user->shouldNotifyForInstitution($this->institution))->toBeTrue();
    });

    test('shouldNotifyForInstitution returns false for unfollowed institution', function () {
        expect($this->user->shouldNotifyForInstitution($this->institution))->toBeFalse();
    });
});
