<?php

use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\Dutiables\DutiablePeriods;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
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
]);

test('representative removal withdraws a term that has not started instead of inverting it', function (string $startDate): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $row = Dutiable::factory()->forDuty($duty)->create(['start_date' => $startDate, 'end_date' => null]);

    app(DutyAssignmentService::class)->syncRepresentatives($duty, []);

    expect($row->fresh())->toBeNull();
})->with(['starts today' => '2026-10-03', 'allocated future term' => '2026-11-01']);

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

    expect($manual->fresh()->end_date->toDateString())->toBe('2026-10-02')
        ->and($source->fresh()->end_date)->toBeNull()
        ->and($otherTenant->fresh()->end_date)->toBeNull()
        ->and($derived->fresh()->end_date)->toBeNull();
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
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->start_date->toDateString())->toBe('2026-10-03')
        ->and($rows->first()->tenant_id)->toBe($tenant->id);
    $activities = Activity::forSubject($duty)->where('event', 'relation_updated')->get();
    expect($activities)->toHaveCount(1)
        ->and($activities->first()->properties->get('relation'))->toBe('current_users')
        ->and($activities->first()->properties->get('attached')[0]['id'])->toBe($user->id);
});

test('end dating through the service propagates to derived seats', function (): void {
    config(['queue.default' => 'sync']);
    $this->travelTo('2026-10-03 12:00:00');
    $sourceDuty = Duty::factory()->create();
    $targetDuty = Duty::factory()->create();
    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);
    $source = Dutiable::factory()->forDuty($sourceDuty)->create(['start_date' => '2026-10-03', 'end_date' => null]);
    $derived = Dutiable::where('via_dutiable_id', $source->id)->firstOrFail();

    app(DutyAssignmentService::class)->endDateDutiables(Dutiable::whereKey($source->id), '2026-10-02');

    expect($source->fresh()->end_date->toDateString())->toBe('2026-10-02')
        ->and($derived->fresh()->end_date->toDateString())->toBe('2026-10-02');
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
    $current = $duty->dutiables()->notEnded()->sole();
    expect($current->id)->not->toBe($historical->id)
        ->and($current->start_date->toDateString())->toBe('2026-10-03')
        ->and($current->end_date)->toBeNull();
});

test('a new manual term cannot share an inclusive boundary with a term in the same seat', function (): void {
    $duty = Duty::factory()->create();
    $term = Dutiable::factory()->forDuty($duty)->create(['start_date' => '2023-04-01', 'end_date' => '2023-04-30']);
    $assignments = app(DutyAssignmentService::class);

    expect(fn () => $assignments->startTerm($duty, $term->dutiable_id, null, ['start_date' => '2023-04-30']))
        ->toThrow(ValidationException::class, __('dutiables.assign.already_assigned'));

    $next = $assignments->startTerm($duty, $term->dutiable_id, null, ['start_date' => '2023-05-01']);
    expect($next->start_date->toDateString())->toBe('2023-05-01');
});

test('representative sync rolls back removals, additions and audit when a write fails', function (): void {
    $duty = Duty::factory()->create();
    $holder = Dutiable::factory()->forDuty($duty)->active()->create();
    [$first, $second] = User::factory()->count(2)->create()->modelKeys();

    Event::listen(DutiableChanged::class, function (DutiableChanged $event) use ($second): void {
        if ($event->modelId === $second) {
            throw new RuntimeException('Assignment write failed');
        }
    });

    expect(fn () => app(DutyAssignmentService::class)->syncRepresentatives($duty, [$first, $second]))
        ->toThrow(RuntimeException::class, 'Assignment write failed');

    expect($duty->dutiables()->sole()->id)->toBe($holder->id)
        ->and($holder->fresh()->end_date)->toBeNull()
        ->and(Activity::forSubject($duty)->where('event', 'relation_updated')->exists())->toBeFalse();
});

test('the overlap scope and timeline agree on one day even if stored times differ', function (): void {
    $row = Dutiable::factory()->create([
        'start_date' => Carbon::parse('2025-02-10 20:00:00'),
        'end_date' => Carbon::parse('2025-02-10 08:00:00'),
    ])->refresh();
    $period = ['start_date' => '2025-02-10', 'end_date' => '2025-02-10'];

    expect(DutiablePeriods::overlap(DutiablePeriods::dates($row), $period))->toBe(1)
        ->and(Dutiable::whereKey($row->id)->overlapping($period['start_date'], $period['end_date'])->exists())->toBeTrue();
});
