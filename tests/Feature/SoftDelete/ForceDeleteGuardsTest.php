<?php

use App\Contracts\GuardsForceDelete;
use App\Models\Calendar;
use App\Models\EventType;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Registration;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyProgram;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The trash view offers permanent deletion for every soft-deletable model, but most of
 * them are referenced by restricting foreign keys (the action always failed) or by
 * cascading ones (the action succeeded while destroying records that must outlive it —
 * submitted registrations, reported problems, service history).
 *
 * Models that own their dependents cascade instead; those are covered at the bottom.
 */
describe('blocked', function (): void {
    test('an institution with meetings cannot be permanently deleted', function (): void {
        $institution = Institution::factory()->create();
        $institution->meetings()->attach(Meeting::factory()->create());

        expect($institution->forceDeleteBlockedReason())->toBeString()
            ->and($institution->forceDeleteBlockedReason())->toContain('1');
    });

    test('a form with submitted registrations cannot be permanently deleted', function (): void {
        $form = Form::factory()->create();
        Registration::factory()->for($form)->create();

        expect($form->forceDeleteBlockedReason())->toBeString();
    });

    test('an event type still used by a calendar event cannot be permanently deleted', function (): void {
        $eventType = EventType::factory()->create();
        Calendar::factory()->create(['event_type_id' => $eventType->id]);

        expect($eventType->forceDeleteBlockedReason())->toBeString();
    });

    test('a resource with reservation history cannot be permanently deleted', function (): void {
        $resource = Resource::factory()->create();
        $resource->reservations()->attach(Reservation::factory()->create(), [
            'quantity' => 1,
            'state' => 'created',
        ]);

        expect($resource->forceDeleteBlockedReason())->toBeString();
    });

    test('an unreferenced record reports no blocker', function (): void {
        expect(EventType::factory()->create()->forceDeleteBlockedReason())->toBeNull()
            ->and(Form::factory()->create()->forceDeleteBlockedReason())->toBeNull()
            ->and(StudyProgram::factory()->create()->forceDeleteBlockedReason())->toBeNull();
    });

    test('the reason names the referencing records rather than being generic', function (): void {
        $eventType = EventType::factory()->create();
        Calendar::factory()->count(2)->create(['event_type_id' => $eventType->id]);

        expect($eventType->forceDeleteBlockedReason())
            ->toContain('2')
            ->not->toBe(__('trash.blocked.has_related_records'));
    });
});

describe('through the controller', function (): void {
    test('a blocked record stays trashed and the user is told why', function (): void {
        $tenant = Tenant::query()->first();
        $admin = makeAdminUser($tenant);

        $eventType = EventType::factory()->create();
        Calendar::factory()->create(['event_type_id' => $eventType->id]);
        $eventType->delete();

        asUser($admin)
            ->delete(route('eventTypes.forceDelete', $eventType->id))
            ->assertRedirect()
            ->assertSessionHas('error', $eventType->forceDeleteBlockedReason());

        $this->assertSoftDeleted('event_types', ['id' => $eventType->id]);
    });

    test('an unblocked record is permanently deleted', function (): void {
        $tenant = Tenant::query()->first();
        $admin = makeAdminUser($tenant);

        $eventType = EventType::factory()->create();
        $eventType->delete();

        asUser($admin)
            ->delete(route('eventTypes.forceDelete', $eventType->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('event_types', ['id' => $eventType->id]);
    });
});

describe('cascading models', function (): void {
    test('a reservation detaches its resources on permanent deletion only', function (): void {
        $reservation = Reservation::factory()->create();
        $reservation->resources()->attach(Resource::factory()->create(), [
            'quantity' => 1,
            'state' => 'created',
        ]);

        $reservation->delete();
        $this->assertDatabaseHas('reservation_resource', ['reservation_id' => $reservation->id]);

        $reservation->forceDelete();
        $this->assertDatabaseMissing('reservation_resource', ['reservation_id' => $reservation->id]);
    });
});

test('every guarded model exposes the reason as an appendable attribute', function (): void {
    // The admin index serializes `force_delete_blocked_reason` so the table can disable
    // the action before it is clicked.
    $models = [
        EventType::factory()->create(),
        Form::factory()->create(),
        StudyProgram::factory()->create(),
        Institution::factory()->create(),
    ];

    foreach ($models as $model) {
        expect($model)->toBeInstanceOf(GuardsForceDelete::class)
            ->and($model->append('force_delete_blocked_reason')->toArray())
            ->toHaveKey('force_delete_blocked_reason');
    }
});
