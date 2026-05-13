<?php

use App\Actions\BackfillExOfficioTargetDuty;
use App\Events\DutiableChanged;
use App\Listeners\SyncExOfficioDutiables;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['queue.default' => 'sync']);
    Event::fake([DutiableChanged::class]);

    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $institution = Institution::factory()->for($this->tenant)->create();
    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);

    $this->user = User::factory()->create();
});

test('creating a source Dutiable fires DutiableChanged', function () {
    Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay(),
        'end_date' => null,
    ]);

    Event::assertDispatched(DutiableChanged::class);
});

test('listener creates derived Dutiable for each ex-officio target', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    $derived = Dutiable::where('duty_id', $this->targetDuty->id)
        ->where('dutiable_id', $this->user->id)
        ->where('via_dutiable_id', $source->id)
        ->first();

    expect($derived)->not->toBeNull()
        ->and($derived->start_date->toDateString())->toBe($source->start_date->toDateString())
        ->and($derived->end_date)->toBeNull();
});

test('listener mirrors end_date change to derived row', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    // Now end-date the source.
    $endDate = now()->subDay()->toDateString();
    $source->end_date = $endDate;
    $source->save();

    $listener->handle(new DutiableChanged($source));

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();
    expect($derived->end_date->toDateString())->toBe($endDate);
});

test('listener does not overwrite independent fields on derived row', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    // Admin edits the derived row's additional_email.
    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();
    $derived->additional_email = 'custom@example.com';
    $derived->save();

    // Source re-saved (e.g. dates changed) → re-sync.
    $source->start_date = now()->subWeek()->toDateString();
    $source->save();
    $listener->handle(new DutiableChanged($source));

    $derived->refresh();
    expect($derived->additional_email)->toBe('custom@example.com');
});

test('listener adopts existing manual row instead of creating a duplicate', function () {
    // User already has a manual Dutiable on the target duty.
    $manualRow = Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    // Should not create a new row.
    $count = Dutiable::where('duty_id', $this->targetDuty->id)
        ->where('dutiable_id', $this->user->id)
        ->count();

    expect($count)->toBe(1);

    $manualRow->refresh();
    expect($manualRow->via_dutiable_id)->toBe($source->id);
});

test('listener deletes derived rows when source is force-deleted', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    expect(Dutiable::where('via_dutiable_id', $source->id)->count())->toBe(1);

    // Simulate force delete: set exists = false on the event model.
    $deletedSource = clone $source;
    $deletedSource->exists = false;

    $listener->handle(new DutiableChanged($deletedSource));

    expect(Dutiable::where('via_dutiable_id', $source->id)->count())->toBe(0);
});

test('listener skips non-User dutiable types', function () {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => 'App\\Models\\Contact', // non-User
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    expect(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(0);
});

test('listener skips derived rows to prevent chains', function () {
    $parentDutiable = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derivedRow = Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => $parentDutiable->id,
    ]);

    $before = Dutiable::count();
    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($derivedRow));

    expect(Dutiable::count())->toBe($before);
});

test('cross-tenant ex-officio sets tenant_id when target supports source tenant', function () {
    $sourceTenant = Tenant::query()->inRandomOrder()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->inRandomOrder()->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    $targetDuty->assignableTenants()->attach($sourceTenant->id, ['quota' => 2]);

    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBe($sourceTenant->id);
});

test('cross-tenant ex-officio does not set tenant_id when target does not support source tenant', function () {
    $sourceTenant = Tenant::query()->inRandomOrder()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->inRandomOrder()->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    // Do NOT add sourceTenant to targetDuty's assignableTenants.

    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBeNull();
});

test('same-tenant ex-officio keeps tenant_id null', function () {
    // Default beforeEach already creates source and target in the same tenant.
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBeNull();
});

test('listener preserves tenant_id when mirroring date changes', function () {
    $sourceTenant = Tenant::query()->inRandomOrder()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->inRandomOrder()->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    $targetDuty->assignableTenants()->attach($sourceTenant->id, ['quota' => 2]);

    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    // Change source dates.
    $source->start_date = now()->subWeek()->toDateString();
    $source->save();
    $listener->handle(new DutiableChanged($source));

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived->tenant_id)->toBe($sourceTenant->id)
        ->and($derived->start_date->toDateString())->toBe($source->start_date->toDateString());
});

test('listener adopts manual row and updates tenant_id when target supports source tenant', function () {
    $sourceTenant = Tenant::query()->inRandomOrder()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->inRandomOrder()->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    $targetDuty->assignableTenants()->attach($sourceTenant->id, ['quota' => 2]);

    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    // Manual row on target duty without tenant_id.
    $manualRow = Dutiable::factory()->create([
        'duty_id' => $targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
        'tenant_id' => null,
    ]);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $listener = new SyncExOfficioDutiables;
    $listener->handle(new DutiableChanged($source));

    $manualRow->refresh();

    expect($manualRow->via_dutiable_id)->toBe($source->id)
        ->and($manualRow->tenant_id)->toBe($sourceTenant->id);
});

test('backfill sets tenant_id for cross-tenant ex-officio', function () {
    $sourceTenant = Tenant::query()->inRandomOrder()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->inRandomOrder()->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    $targetDuty->assignableTenants()->attach($sourceTenant->id, ['quota' => 2]);

    // Active source dutiable already exists before the ex-officio link is created.
    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    // Backfill after adding the target.
    BackfillExOfficioTargetDuty::execute($sourceDuty, [$targetDuty->id], []);

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBe($sourceTenant->id);
});
