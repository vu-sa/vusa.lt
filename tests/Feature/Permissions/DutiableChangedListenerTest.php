<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Unlike ExOfficioSyncTest, nothing here is faked and no listener is invoked by hand.
 * These tests drive the real path — Eloquent fires DutiableChanged, the queued
 * listeners are dispatched and executed — which is the only way to catch a payload
 * that cannot survive the trip through the queue.
 */
beforeEach(function () {
    config(['queue.default' => 'sync']);

    $this->tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($this->tenant)->create();

    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);

    $this->user = User::factory()->create();
});

test('deleting a source dutiable cascades to derived rows through the real queue', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    // The `saved` event already ran the sync, so the derived row exists.
    expect(Dutiable::where('via_dutiable_id', $source->id)->count())->toBe(1);

    $sourceId = $source->id;
    $source->delete();

    expect(Dutiable::where('via_dutiable_id', $sourceId)->count())->toBe(0);
});

test('deleting a dutiable does not fail any queued jobs', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $source->delete();

    // A model payload could not be restored here and would blow up both listeners.
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

test('deleting a dutiable invalidates the holder permission caches', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    Cache::put('index-permissions-'.$this->user->id, ['stale'], 1800);
    Cache::put('create-permissions-'.$this->user->id, ['stale'], 1800);

    $source->delete();

    // Losing a duty changes permissions, so the cached maps must not survive it.
    expect(Cache::has('index-permissions-'.$this->user->id))->toBeFalse()
        ->and(Cache::has('create-permissions-'.$this->user->id))->toBeFalse();
});

test('a contact dutiable does not invalidate a user that shares its id', function () {
    Cache::put('index-permissions-'.$this->user->id, ['fresh'], 1800);
    Cache::put('create-permissions-'.$this->user->id, ['fresh'], 1800);

    // Same id, different morph type. The pivot's `user()` relation does not filter on
    // dutiable_type, so a naive lookup would resolve this to the User above.
    Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => 'App\\Models\\Contact',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    expect(Cache::has('index-permissions-'.$this->user->id))->toBeTrue()
        ->and(Cache::has('create-permissions-'.$this->user->id))->toBeTrue();
});
