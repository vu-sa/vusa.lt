<?php

use App\Listeners\SyncContactSearchIndexes;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['queue.default' => 'sync']);

    $this->tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($this->tenant)->create();

    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);

    $this->user = User::factory()->create();
});

test('deleting a source dutiable cascades to derived rows through the real queue', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
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

test('deleting a dutiable does not fail any queued jobs', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $source->delete();

    // A model payload could not be restored here and would blow up both listeners.
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

test('deleting a dutiable invalidates the holder permission caches', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
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

test('deleting a duty assignment revokes a warmed policy grant after commit without a worker', function (): void {
    $this->freezeTime();
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate('news.update.padalinys', 'web'));
    $this->sourceDuty->assignRole($role);
    $source = Dutiable::factory()->forDuty($this->sourceDuty)->forUser($this->user)->create([
        'start_date' => today()->subDay(),
        'end_date' => null,
    ]);
    $news = News::factory()->for($this->tenant)->create();
    $gate = Gate::forUser($this->user);

    expect($gate->allows('update', $news))->toBeTrue()
        ->and(Cache::has('auth:duties:'.$this->user->id))->toBeTrue();
    Queue::fake();

    DB::transaction(function () use ($source): void {
        $source->delete();

        expect(Cache::has('auth:duties:'.$this->user->id))->toBeTrue();
    });

    expect($gate->allows('update', $news))->toBeFalse();
    Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $job): bool => $job->class === SyncContactSearchIndexes::class);
});

test('a contact dutiable does not invalidate a user that shares its id', function (): void {
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
