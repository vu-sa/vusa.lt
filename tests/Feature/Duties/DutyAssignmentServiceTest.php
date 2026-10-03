<?php

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DutyAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

test('representative removal respects inclusive end dates and allocated future starts', function (?string $endDate, string $startDate, string $expectedEndDate): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $row = Dutiable::factory()->forDuty($duty)->create([
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    app(DutyAssignmentService::class)->syncRepresentatives($duty, []);

    expect($row->fresh()->end_date->toDateString())->toBe($expectedEndDate);
})->with([
    'open term' => [null, '2026-09-01', '2026-10-02'],
    'last day today' => ['2026-10-03', '2026-09-01', '2026-10-02'],
    'future end' => ['2026-11-01', '2026-09-01', '2026-10-02'],
    'historical term' => ['2026-09-30', '2026-09-01', '2026-09-30'],
    'allocated future term' => [null, '2026-11-01', '2026-10-02'],
]);

test('representative lists only change manual rows of the selected duty and tenant', function (bool $crossTenant): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $otherDuty = Duty::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenantId = $crossTenant ? $tenant->id : null;
    $otherTenantId = $crossTenant ? null : $tenant->id;
    $user = User::factory()->create();
    $source = Dutiable::factory()->forDuty($otherDuty)->forUser($user)->active()->create();
    $manual = Dutiable::factory()->forDuty($duty)->forUser($user)->active()->create(['tenant_id' => $tenantId]);
    $otherTenant = Dutiable::factory()->forDuty($duty)->forUser($user)->active()->create(['tenant_id' => $otherTenantId]);
    $derived = Dutiable::factory()->forDuty($duty)->active()->create([
        'via_dutiable_id' => $source->id,
        'tenant_id' => $tenantId,
    ]);

    app(DutyAssignmentService::class)->syncRepresentatives($duty, [], $tenantId);

    expect($manual->fresh()->end_date->toDateString())->toBe('2026-10-02');
    expect($source->fresh()->end_date)->toBeNull();
    expect($otherTenant->fresh()->end_date)->toBeNull();
    expect($derived->fresh()->end_date)->toBeNull();
})->with(['owning tenant' => false, 'assignable tenant' => true]);

test('duplicate representative ids create one audited membership and repeated syncs keep it', function (): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $service = app(DutyAssignmentService::class);

    $service->syncRepresentatives($duty, [$user->id, $user->id], $tenant->id);
    $service->syncRepresentatives($duty, [$user->id], $tenant->id);

    $rows = $duty->dutiables()->where('dutiable_id', $user->id)->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->start_date->toDateString())->toBe('2026-10-02');
    expect($rows->first()->tenant_id)->toBe($tenant->id);
    $activities = Activity::forSubject($duty)->where('event', 'relation_updated')->get();
    expect($activities)->toHaveCount(1);
    expect($activities->first()->properties->get('relation'))->toBe('users');
    expect($activities->first()->properties->get('attached')[0]['id'])->toBe($user->id);
});

test('end dating through the service propagates to derived seats', function (): void {
    config(['queue.default' => 'sync']);
    $this->travelTo('2026-10-03 12:00:00');
    $sourceDuty = Duty::factory()->create();
    $targetDuty = Duty::factory()->create();
    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);
    $source = Dutiable::factory()->forDuty($sourceDuty)->active()->create();
    $derived = Dutiable::where('via_dutiable_id', $source->id)->firstOrFail();

    app(DutyAssignmentService::class)->endDateDutiables(Dutiable::whereKey($source->id), '2026-10-02');

    expect($source->fresh()->end_date->toDateString())->toBe('2026-10-02');
    expect($derived->fresh()->end_date->toDateString())->toBe('2026-10-02');
});

test('returning representatives get a new term without overwriting their history', function (): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $user = User::factory()->create();
    $historical = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
    ]);

    app(DutyAssignmentService::class)->syncRepresentatives($duty, [$user->id]);

    expect($historical->fresh()->end_date->toDateString())->toBe('2026-06-30');
    $current = $duty->dutiables()->current()->sole();
    expect($current->id)->not->toBe($historical->id);
    expect($current->start_date->toDateString())->toBe('2026-10-02');
    expect($current->end_date)->toBeNull();
});
