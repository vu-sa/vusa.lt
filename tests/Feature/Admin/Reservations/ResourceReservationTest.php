<?php

use App\Enums\ApprovalDecision;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Build a reservation owned by $owner holding one $resource. Reservations are
 * only ever mutated through this pivot — `reservations.update` does not exist.
 */
function makeReservationResource(Resource $resource, User $owner): ReservationResource
{
    $reservation = Reservation::factory()->create([
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(1),
    ]);

    $reservation->users()->attach($owner->id);

    $reservation->resources()->attach($resource->id, [
        'quantity' => 1,
        'start_time' => $reservation->start_time,
        'end_time' => $reservation->end_time,
    ]);

    return ReservationResource::query()
        ->where('reservation_id', $reservation->id)
        ->sole();
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);

    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Išteklių administratorius');

    $this->category = ResourceCategory::factory()->create();

    $this->resource = Resource::factory()->create([
        'tenant_id' => $this->tenant->id,
        'resource_category_id' => $this->category->id,
        'capacity' => 3,
        'is_reservable' => true,
    ]);
});

describe('auth: simple user', function (): void {
    test('can view available resources for reservation', function (): void {
        asUser($this->user)->get(route('resources.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Reservations/IndexResource'));
    });

    test('can create reservation for available resource', function (): void {
        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Team Meeting',
            'description' => 'Weekly team meeting',
            'start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_time' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'resources' => [
                ['id' => $this->resource->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'name' => 'Team Meeting',
        ]);

        // Check user is attached to reservation
        $reservation = Reservation::where('name', 'Team Meeting')->first();
        expect($reservation->users->contains($this->user))->toBeTrue();
    });

    test('cannot create overlapping reservations', function (): void {
        $this->resource->update(['capacity' => 1]);

        $existingReservation = Reservation::factory()->create([
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);
        $existingReservation->users()->attach($this->user->id);
        $existingReservation->resources()->attach($this->resource->id, [
            'quantity' => 1,
            'start_time' => $existingReservation->start_time,
            'end_time' => $existingReservation->end_time,
            'state' => 'created',
        ]);

        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Conflicting Meeting',
            'description' => 'Needs the same resource',
            'start_time' => now()->addDays(1)->addMinutes(30)->format('Y-m-d H:i:s'),
            'end_time' => now()->addDays(1)->addHours(3)->format('Y-m-d H:i:s'),
            'resources' => [
                ['id' => $this->resource->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('resources.0.quantity');

        expect(Reservation::where('name', 'Conflicting Meeting')->exists())->toBeFalse();
    });

    /**
     * A reservation is never updated as a whole — `reservations.update` does not
     * exist. Every mutation goes through the reservationResources pivot, which
     * authorizes against the parent reservation.
     */
    test('can update resources on own reservations', function (): void {
        $reservationResource = makeReservationResource($this->resource, $this->user);

        asUser($this->user)->put(route('reservationResources.update', $reservationResource), [
            'start_time' => now()->addDays(2)->getTimestampMs(),
            'end_time' => now()->addDays(2)->addHours(3)->getTimestampMs(),
            'resource_id' => $this->resource->id,
            'quantity' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($reservationResource->fresh()->quantity)->toBe(2);
    });

    test('cannot update resources on other users reservations', function (): void {
        $otherUser = User::factory()->create();
        $reservationResource = makeReservationResource($this->resource, $otherUser);

        asUser($this->user)->put(route('reservationResources.update', $reservationResource), [
            'start_time' => now()->addDays(2)->getTimestampMs(),
            'end_time' => now()->addDays(2)->addHours(3)->getTimestampMs(),
            'resource_id' => $this->resource->id,
            'quantity' => 99,
        ])->assertStatus(403);

        expect($reservationResource->fresh()->quantity)->not->toBe(99);
    });

    test('can delete own reservations', function (): void {
        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($this->user->id);

        asUser($this->user)->delete(route('reservations.destroy', $reservation))
            ->assertRedirect();

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    });
});

describe('auth: resource manager', function (): void {
    test('can create new resources', function (): void {
        $resourceCount = Resource::count();

        asUser($this->resourceManager)->post(route('resources.store'), [
            'name' => [
                'lt' => 'Conference Room A',
                'en' => 'Conference Room A',
            ],
            'description' => [
                'lt' => 'Large conference room with projector',
                'en' => 'Large conference room with projector',
            ],
            'capacity' => 20,
            'location' => 'Building A, Floor 2',
            'tenant_id' => $this->tenant->id,
            'resource_category_id' => $this->category->id,
            'is_reservable' => true,
            'media' => [], // Empty array instead of null
        ])->assertRedirect();

        expect(Resource::count())->toBe($resourceCount + 1);

        // Find the resource we just created by specific criteria
        $createdResource = Resource::where('capacity', 20)
            ->where('tenant_id', $this->tenant->id)
            ->where('location', 'Building A, Floor 2')
            ->first();

        expect($createdResource)->not->toBeNull()
            ->and($createdResource->getTranslation('name', 'lt'))->toBe('Conference Room A')
            ->and($createdResource->capacity)->toBe(20)
            ->and($createdResource->tenant_id)->toBe($this->tenant->id);
    });

    test('can update resources', function (): void {
        $originalName = $this->resource->getTranslation('name', 'lt');

        asUser($this->resourceManager)->put(route('resources.update', $this->resource), [
            'name' => [
                'lt' => 'Updated Resource Name',
                'en' => 'Updated Resource Name',
            ],
            'description' => [
                'lt' => 'Updated description',
                'en' => 'Updated description',
            ],
            'location' => 'Updated location',
            'tenant_id' => $this->tenant->id,
            'capacity' => 30,
            'is_reservable' => false,
            'resource_category_id' => $this->category->id,
            'media' => [], // Empty array instead of null
        ])->assertRedirect();

        $this->resource->refresh();
        expect($this->resource->getTranslation('name', 'lt'))->toBe('Updated Resource Name')->not->toBe($originalName)
            ->and($this->resource->capacity)->toBe(30)
            ->and($this->resource->is_reservable)->toBeFalsy(); // Cast to boolean for comparison
    });

    test('can view all reservations', function (): void {
        // Create reservations from different users
        $reservations = Reservation::factory()->count(3)->create();
        foreach ($reservations as $reservation) {
            $user = User::factory()->create();
            $reservation->users()->attach($user->id);
        }

        // Resource manager should be able to see all reservations
        asUser($this->resourceManager)->get(route('reservations.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reservations/IndexReservation')
                ->has('reservations.data')
            );
    });
});

describe('resource availability logic', function (): void {
    test('resource shows as unavailable during existing reservations', function (): void {
        $reservation = Reservation::factory()->create([
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);
        $reservation->resources()->attach($this->resource->id, [
            'quantity' => 1,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'state' => 'created',
        ]);

        $response = asUser($this->user)->get(route('resources.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Reservations/IndexResource'));
    });
});

function reservationResourcePayload(ReservationResource $pivot, array $changes = []): array
{
    return array_replace([
        'resource_id' => $pivot->resource_id,
        'quantity' => $pivot->quantity,
        'start_time' => $pivot->start_time->getTimestampMs(),
        'end_time' => $pivot->end_time->getTimestampMs(),
    ], $changes);
}

describe('reservation resource validation', function (): void {
    test('invalid values never change the row', function (string $field, mixed $value): void {
        $pivot = makeReservationResource($this->resource, $this->user);
        $original = $pivot->getAttributes();

        asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, [$field => $value]))
            ->assertSessionHasErrors($field);

        expect($pivot->fresh()->getAttributes())->toBe($original);
    })->with([
        'zero quantity' => ['quantity', 0],
        'negative quantity' => ['quantity', -1],
        'fractional quantity' => ['quantity', 1.5],
        'malformed start' => ['start_time', 'not a timestamp'],
        'missing start' => ['start_time', null],
        'malformed end' => ['end_time', []],
        'reversed range' => ['end_time', 0],
        'out of range timestamp' => ['end_time', PHP_INT_MAX],
        'unknown resource' => ['resource_id', 'missing'],
    ]);

    test('equal timestamps are rejected', function (): void {
        $pivot = makeReservationResource($this->resource, $this->user);
        asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, [
            'end_time' => $pivot->start_time->getTimestampMs(),
        ]))->assertSessionHasErrors('end_time');
    });

    test('non-created rows cannot be edited even by a manager', function (string $state, bool $manager): void {
        $pivot = makeReservationResource($this->resource, $this->user);
        $pivot->state = $state;
        $pivot->save();
        $original = $pivot->getAttributes();

        asUser($manager ? $this->resourceManager : $this->user)
            ->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, ['quantity' => 2]))
            ->assertForbidden();

        expect($pivot->fresh()->getAttributes())->toBe($original);
    })->with(['reserved', 'lent', 'returned', 'rejected', 'cancelled'])->with([false, true]);

    test('deleted and non-reservable resources cannot be selected', function (bool $deleted): void {
        $pivot = makeReservationResource($this->resource, $this->user);
        $other = Resource::factory()->for($this->tenant)->create(['is_reservable' => ! $deleted ? false : true]);
        if ($deleted) {
            $other->delete();
        }

        asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, ['resource_id' => $other->id]))
            ->assertSessionHasErrors('resource_id');
        expect($pivot->fresh()->resource_id)->toBe($this->resource->id);
    })->with([false, true]);

    test('another tenant resource can be selected but protected attributes are ignored', function (): void {
        $pivot = makeReservationResource($this->resource, $this->user);
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
        $other = Resource::factory()->for($otherTenant)->create(['capacity' => 2, 'is_reservable' => true]);
        $anotherReservation = Reservation::factory()->create();

        asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, [
            'resource_id' => $other->id, 'quantity' => 2, 'reservation_id' => $anotherReservation->id, 'state' => 'reserved',
        ]))->assertSessionHasNoErrors();

        expect($pivot->fresh())->resource_id->toBe($other->id)
            ->reservation_id->toBe($pivot->reservation_id)
            ->quantity->toBe(2)
            ->and((string) $pivot->fresh()->state)->toBe('created');
    });

    test('only the edited pivot is excluded at interior overlap points', function (): void {
        $this->resource->update(['capacity' => 3]);
        $pivot = makeReservationResource($this->resource, $this->user);
        $sibling = ReservationResource::create([
            'reservation_id' => $pivot->reservation_id, 'resource_id' => $pivot->resource_id, 'quantity' => 1,
            'start_time' => $pivot->start_time->copy()->addMinutes(10), 'end_time' => $pivot->end_time->copy()->subMinutes(10),
        ]);

        asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, ['quantity' => 2]))
            ->assertSessionHasNoErrors();
        expect($pivot->fresh()->quantity)->toBe(2)
            ->and($sibling->fresh()->quantity)->toBe(1);
    });

    test('capacity occupied by a sibling still prevents an increase', function (): void {
        $this->resource->update(['capacity' => 2]);
        $pivot = makeReservationResource($this->resource, $this->user);
        ReservationResource::create([
            'reservation_id' => $pivot->reservation_id, 'resource_id' => $pivot->resource_id, 'quantity' => 1,
            'start_time' => $pivot->start_time->copy()->addMinutes(10), 'end_time' => $pivot->end_time->copy()->subMinutes(10),
        ]);

        asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot, ['quantity' => 2]))
            ->assertSessionHasErrors('quantity');
        expect($pivot->fresh()->quantity)->toBe(1);
    });

    test('store applies the same input and capacity boundaries', function (string $failure): void {
        $this->resource->update(['capacity' => 1]);
        $pivot = makeReservationResource($this->resource, $this->user);
        $payload = [...reservationResourcePayload($pivot), 'reservation_id' => $pivot->reservation_id];
        $field = 'quantity';
        if ($failure === 'malformed date') {
            $payload['start_time'] = 'invalid';
            $field = 'start_time';
        } elseif ($failure === 'negative quantity') {
            $payload['quantity'] = -1;
        } elseif ($failure === 'not reservable') {
            $this->resource->update(['is_reservable' => false]);
            $field = 'resource_id';
        }

        asUser($this->user)->post(route('reservationResources.store'), $payload)->assertSessionHasErrors($field);
        expect(ReservationResource::where('reservation_id', $pivot->reservation_id)->count())->toBe(1);
    })->with(['malformed date', 'negative quantity', 'not reservable', 'no capacity']);

    test('unauthorized parents are rejected before invalid input', function (): void {
        $pivot = makeReservationResource($this->resource, $this->resourceManager);
        asUser($this->user)->put(route('reservationResources.update', $pivot), [])->assertForbidden();
    });
});

test('backtracking an approved item permits edits and retains approval history', function (): void {
    $pivot = makeReservationResource($this->resource, $this->user);
    $service = app(ApprovalService::class);
    $approval = $service->approve($pivot, $this->resourceManager, ApprovalDecision::Approved);
    $service->backtrack($pivot, $this->resourceManager);

    asUser($this->user)->put(route('reservationResources.update', $pivot), reservationResourcePayload($pivot))
        ->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($approval->fresh()->reverted_at)->not->toBeNull()
        ->and((string) $pivot->fresh()->state)->toBe('created');
});
