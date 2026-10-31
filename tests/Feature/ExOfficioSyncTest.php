<?php

use App\Actions\Dutiables\SyncExOfficioTargets;
use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['queue.default' => 'sync']);
    Event::fake([DutiableChanged::class]);

    $this->tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($this->tenant)->create();
    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);

    $this->user = User::factory()->create();
});

test('creating a source Dutiable fires DutiableChanged', function (): void {
    Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay(),
        'end_date' => null,
    ]);

    Event::assertDispatched(DutiableChanged::class);
});

test('model save creates derived Dutiable for each ex-officio target', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derived = Dutiable::where('duty_id', $this->targetDuty->id)
        ->where('dutiable_id', $this->user->id)
        ->where('via_dutiable_id', $source->id)
        ->first();

    expect($derived)->not->toBeNull()
        ->and($derived->start_date->toDateString())->toBe($source->start_date->toDateString())
        ->and($derived->end_date)->toBeNull();
});

test('model save mirrors end_date change to derived row', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $endDate = now()->subDay()->toDateString();
    $source->end_date = $endDate;
    $source->save();

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();
    expect($derived->end_date->toDateString())->toBe($endDate);
});

test('model save does not overwrite independent fields on derived row', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();
    $derived->additional_email = 'custom@example.com';
    $derived->save();

    $source->start_date = now()->subWeek()->toDateString();
    $source->save();

    $derived->refresh();
    expect($derived->additional_email)->toBe('custom@example.com');
});

test('model save preserves an independent manual term beside its derived term', function (): void {
    $manualRow = Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $count = Dutiable::where('duty_id', $this->targetDuty->id)
        ->where('dutiable_id', $this->user->id)
        ->count();

    expect($count)->toBe(2);

    $manualRow->refresh();
    expect($manualRow->via_dutiable_id)->toBeNull()
        ->and($manualRow->start_date->toDateString())->toBe(now()->subYear()->toDateString());
});

test('model save deletes derived rows when source is force-deleted', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    expect(Dutiable::where('via_dutiable_id', $source->id)->count())->toBe(1);

    $source->delete();

    expect(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(0);
});

test('model save skips non-User dutiable types', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => 'App\\Models\\Contact', // non-User
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    expect(Dutiable::where('duty_id', $this->targetDuty->id)->count())->toBe(0);
});

test('model save skips derived rows to prevent chains', function (): void {
    $parentDutiable = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derivedRow = Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => $parentDutiable->id,
    ]);

    $before = Dutiable::count();
    $derivedRow->save();

    expect(Dutiable::count())->toBe($before);
});

test('cross-tenant ex-officio sets tenant_id when target supports source tenant', function (): void {
    $sourceTenant = Tenant::query()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->first();

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
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBe($sourceTenant->id);
});

test('cross-tenant ex-officio does not set tenant_id when target does not support source tenant', function (): void {
    $sourceTenant = Tenant::query()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();

    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBeNull();
});

test('same-tenant ex-officio keeps tenant_id null', function (): void {
    $source = Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBeNull();
});

test('model save preserves tenant_id when mirroring date changes', function (): void {
    $sourceTenant = Tenant::query()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->first();

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
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $source->start_date = now()->subWeek()->toDateString();
    $source->save();

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived->tenant_id)->toBe($sourceTenant->id)
        ->and($derived->start_date->toDateString())->toBe($source->start_date->toDateString());
});

test('model save never changes an independent manual term tenant', function (): void {
    $sourceTenant = Tenant::query()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    $targetDuty->assignableTenants()->attach($sourceTenant->id, ['quota' => 2]);

    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    $manualRow = Dutiable::factory()->create([
        'duty_id' => $targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
        'tenant_id' => null,
    ]);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    $manualRow->refresh();

    expect($manualRow->via_dutiable_id)->toBeNull()
        ->and($manualRow->tenant_id)->toBeNull();
});

test('link sync sets tenant_id for cross-tenant ex-officio', function (): void {
    $sourceTenant = Tenant::query()->first();
    $targetTenant = Tenant::query()->where('id', '!=', $sourceTenant->id)->first();

    if (! $targetTenant) {
        $this->markTestSkipped('Need at least 2 tenants in the database.');
    }

    $sourceInstitution = Institution::factory()->for($sourceTenant)->create();
    $targetInstitution = Institution::factory()->for($targetTenant)->create();

    $sourceDuty = Duty::factory()->for($sourceInstitution)->create();
    $targetDuty = Duty::factory()->for($targetInstitution)->create();
    $targetDuty->assignableTenants()->attach($sourceTenant->id, ['quota' => 2]);

    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    SyncExOfficioTargets::execute($sourceDuty, [$targetDuty->id]);

    $derived = Dutiable::where('via_dutiable_id', $source->id)->first();

    expect($derived)->not->toBeNull()
        ->and($derived->tenant_id)->toBe($sourceTenant->id);
});
