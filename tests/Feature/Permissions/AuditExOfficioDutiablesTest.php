<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['queue.default' => 'sync']);

    $this->tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($this->tenant)->create();

    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty);

    $this->user = User::factory()->create();
});

test('reports a target duty held without its source duty', function () {
    // The shape the nullOnDelete FK leaves behind: a target-duty row with no link
    // back to the source, and no source duty held.
    Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    Artisan::call('duties:audit-ex-officio');

    expect(Artisan::output())->toContain($this->user->name);
});

test('does not report a target duty when the source duty is still held', function () {
    Dutiable::factory()->create([
        'duty_id' => $this->sourceDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => null,
        'via_dutiable_id' => null,
    ]);

    Artisan::call('duties:audit-ex-officio');

    // The source row's own sync creates a properly-linked derived row, which the
    // audit ignores because via_dutiable_id is set.
    expect(Artisan::output())->toContain('No orphaned ex-officio duties found');
});

test('ignores expired target duties', function () {
    Dutiable::factory()->create([
        'duty_id' => $this->targetDuty->id,
        'dutiable_id' => $this->user->id,
        'dutiable_type' => User::class,
        'start_date' => now()->subYear()->toDateString(),
        'end_date' => now()->subMonth()->toDateString(),
        'via_dutiable_id' => null,
    ]);

    Artisan::call('duties:audit-ex-officio');

    expect(Artisan::output())->toContain('No orphaned ex-officio duties found');
});
