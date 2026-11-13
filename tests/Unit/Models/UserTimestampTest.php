<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(Carbon::parse('2026-11-01 10:00:00', 'UTC'));
    $this->user = makeUser(Tenant::query()->firstOrFail());
    $this->editedAt = $this->user->fresh()->updated_at;
    $this->travelTo(Carbon::parse('2026-11-13 18:00:00', 'UTC'));
});

test('settings, tokens and visits leave the member record untouched', function (): void {
    asUser($this->user)->get(route('dashboard'));
    asUser($this->user)->patch(route('api.v1.admin.user-preferences.trackRecentPage'), ['route' => 'meetings.index', 'params' => []]);
    $this->user->fresh()->muteNotificationsUntil(now()->addWeek());
    $this->user->fresh()->forceFill(['microsoft_token' => 'token'])->save();

    expect($this->user->fresh()->updated_at->equalTo($this->editedAt))->toBeTrue()
        ->and($this->user->fresh()->last_action)->not->toBeNull();
});

test('editing the person moves the update time, even alongside a setting', function (): void {
    $this->user->fresh()->forceFill(['phone' => '+37060000000', 'ui_preferences' => ['theme' => 'dark']])->save();

    expect($this->user->fresh()->updated_at->toISOString())->toBe('2026-11-13T18:00:00.000000Z');
});
