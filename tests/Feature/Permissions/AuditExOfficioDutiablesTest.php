<?php

use App\Actions\Dutiables\FindSuspectExOfficioDutiables;
use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

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

test('does not report a target duty whose holder never held a source', function (): void {
    Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
        'assignment_origin' => 'unknown',
    ]);

    Artisan::call('duties:audit-ex-officio');

    expect(Artisan::output())->not->toContain($this->user->name);
});

test('does not report a target duty when the source duty is still held', function (): void {
    Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
        'assignment_origin' => 'unknown',
    ]);

    Artisan::call('duties:audit-ex-officio');

    // The source row's own sync creates a properly-linked derived row, which the
    // audit ignores because via_dutiable_id is set.
    expect(Artisan::output())->toContain('No ex-officio assignments need review.');
});

test('ignores expired target duties', function (): void {
    Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => now()->subMonth()->toDateString(),
        'via_dutiable_id' => null,
        'assignment_origin' => 'unknown',
    ]);

    Artisan::call('duties:audit-ex-officio');

    expect(Artisan::output())->toContain('No ex-officio assignments need review.');
});

test('multiple historical matching sources produce one timeline suspect id', function (): void {
    $other = Duty::factory()->create(['institution_id' => $this->sourceDuty->institution_id]);
    $other->exOfficioTargetDuties()->attach($this->targetDuty);
    Event::fake([DutiableChanged::class]);
    foreach ([$this->sourceDuty, $other] as $duty) {
        Dutiable::factory()->create([
            'duty_id' => $duty->id, 'dutiable_id' => $this->user->id,
            'start_date' => today()->subMonths(2), 'end_date' => today()->subMonth(),
        ]);
    }
    $row = Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id, 'dutiable_id' => $this->user->id,
        'start_date' => today()->subMonths(2), 'end_date' => null, 'assignment_origin' => 'unknown',
    ]);
    expect(FindSuspectExOfficioDutiables::idsForDuties([$this->targetDuty->id]))->toBe([$row->id]);
});

test('confirmed manual and future assignments do not need orphan review', function (): void {
    foreach (['manual', 'unknown'] as $origin) {
        Dutiable::factory()->create([
            'duty_id' => $this->targetDuty->id, 'dutiable_id' => $this->user->id,
            'start_date' => $origin === 'manual' ? today()->subDay() : today()->addDay(),
            'end_date' => null, 'assignment_origin' => $origin,
        ]);
    }
    expect(FindSuspectExOfficioDutiables::idsForDuties([$this->targetDuty->id]))->toBeEmpty();
});

test('handover noise is ignored but a longer historical overlap is reported', function (): void {
    Event::fake([DutiableChanged::class]);
    $other = Duty::factory()->create(['institution_id' => $this->sourceDuty->institution_id]);
    $other->exOfficioTargetDuties()->attach($this->targetDuty);
    Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id, 'dutiable_id' => $this->user->id,
        'start_date' => today()->subMonth(), 'end_date' => null, 'assignment_origin' => 'unknown',
    ]);
    $source = Dutiable::factory()->create([
        'duty_id' => $other->id, 'dutiable_id' => $this->user->id,
        'start_date' => today(), 'end_date' => today(),
    ]);
    expect(FindSuspectExOfficioDutiables::idsForDuties([$this->targetDuty->id]))->toBeEmpty();
    $source->update(['start_date' => today()->subMonth(), 'end_date' => today()->subDay()]);
    expect(FindSuspectExOfficioDutiables::idsForDuties([$this->targetDuty->id]))->toHaveCount(1);
});

test('reports ended overlapping source terms and scans ended targets unless current is requested', function (): void {
    Event::fake([DutiableChanged::class]);
    Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id, 'dutiable_id' => $this->user->id,
        'start_date' => '2020-01-01', 'end_date' => '2020-12-31',
    ]);
    Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id, 'dutiable_id' => $this->user->id,
        'start_date' => '2020-01-01', 'end_date' => '2020-12-31', 'assignment_origin' => 'unknown',
    ]);
    Artisan::call('duties:audit-ex-officio');
    expect(Artisan::output())->toContain($this->user->name)->toContain('link');
    Artisan::call('duties:audit-ex-officio', ['--current' => true]);
    expect(Artisan::output())->not->toContain($this->user->name);
});
