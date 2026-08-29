<?php

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

/**
 * The purge path used to call duties()->detach() — a raw pivot delete that
 * fires no model events, so the permission-cache reset (HandleDutiableChange)
 * and the ex-officio cascade (Dutiable::booted()) both silently skipped.
 * See issue #623.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
});

test('force-deleting a user writes each dutiable row through the model layer', function (): void {
    $target = makeUser($this->tenant);
    $target->duties()->attach(Duty::factory()->for(Institution::factory()->for($this->tenant))->create(), ['start_date' => now()->subDay()]);
    $target->duties()->attach(Duty::factory()->for(Institution::factory()->for($this->tenant))->create(), ['start_date' => now()->subDay()]);

    $rowCount = $target->dutiables()->count();
    $target->delete();

    // Faked after the fixtures: creating and attaching duties also dispatches
    // DutiableChanged (saved), which is not what this test measures.
    Event::fake([DutiableChanged::class]);

    asUser($this->admin)
        ->delete(route('users.forceDelete', $target))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(User::withTrashed()->find($target->id))->toBeNull();
    $this->assertDatabaseMissing('dutiables', ['dutiable_id' => $target->id]);
    // One deleted event per removed row — that event is what resets the
    // permission cache; a raw detach() dispatches none.
    Event::assertDispatchedTimes(DutiableChanged::class, $rowCount);
});

test('force-deleting a user cascades their ex-officio derived rows', function (): void {
    $institution = Institution::factory()->for($this->tenant)->create();
    $sourceDuty = Duty::factory()->for($institution)->create();
    $targetDuty = Duty::factory()->for($institution)->create();
    $sourceDuty->exOfficioTargetDuties()->attach($targetDuty);

    $target = makeUser($this->tenant);
    $source = Dutiable::factory()->create([
        'duty_id' => $sourceDuty->id,
        'dutiable_id' => $target->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay(),
        'end_date' => null,
    ]);
    // The derived seat the source grants on the target duty.
    Dutiable::factory()->create([
        'duty_id' => $targetDuty->id,
        'dutiable_id' => $target->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'via_dutiable_id' => $source->id,
        'start_date' => now()->subDay(),
        'end_date' => null,
    ]);
    $target->delete();

    Event::fake([DutiableChanged::class]);

    asUser($this->admin)
        ->delete(route('users.forceDelete', $target))
        ->assertRedirect()
        ->assertSessionHas('success');

    // The derived row must be deleted as a row, not survive via the FK's
    // nullOnDelete with via_dutiable_id = null.
    $this->assertDatabaseMissing('dutiables', ['dutiable_id' => $target->id]);
    $this->assertDatabaseMissing('dutiables', ['via_dutiable_id' => $source->id]);
});
