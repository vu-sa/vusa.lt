<?php

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);

    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Resource Manager');

    $this->category = ResourceCategory::factory()->create();
    $this->resource = Resource::factory()->create([
        'tenant_id' => $this->tenant->id,
        'resource_category_id' => $this->category->id,
    ]);

    $this->reservation = Reservation::factory()->create([
        'start_time' => now()->addDays(1),
        'end_time' => now()->addDays(1)->addHours(2),
    ]);
    $this->reservation->users()->attach($this->user->id);
    $this->reservation->resources()->attach($this->resource->id, [
        'quantity' => 1,
        'start_time' => $this->reservation->start_time,
        'end_time' => $this->reservation->end_time,
        'state' => 'created',
    ]);

    $this->reservationResource = ReservationResource::query()
        ->where('reservation_id', $this->reservation->id)
        ->where('resource_id', $this->resource->id)
        ->first();
});

describe('ApprovalController@store', function () {
    test('resource manager can approve a reservation resource', function () {
        asUser($this->resourceManager)
            ->post(route('approvals.store'), [
                'approvable_type' => 'reservation_resource',
                'approvable_id' => (string) $this->reservationResource->id,
                'decision' => 'approved',
                'step' => 1,
                'notes' => 'Approved for team event',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('approvals', [
            'approvable_id' => (string) $this->reservationResource->id,
            'decision' => ApprovalDecision::Approved->value,
            'user_id' => $this->resourceManager->id,
        ]);
    });

    test('resource manager can reject a reservation resource', function () {
        asUser($this->resourceManager)
            ->post(route('approvals.store'), [
                'approvable_type' => 'reservation_resource',
                'approvable_id' => (string) $this->reservationResource->id,
                'decision' => 'rejected',
                'step' => 1,
                'notes' => 'Resource unavailable',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('approvals', [
            'approvable_id' => (string) $this->reservationResource->id,
            'decision' => ApprovalDecision::Rejected->value,
        ]);
    });

    test('regular user cannot approve a reservation resource', function () {
        asUser($this->user)
            ->post(route('approvals.store'), [
                'approvable_type' => 'reservation_resource',
                'approvable_id' => (string) $this->reservationResource->id,
                'decision' => 'approved',
                'step' => 1,
            ])
            ->assertSessionHas('error');
    });

    test('validation fails for invalid approvable type', function () {
        asUser($this->resourceManager)
            ->post(route('approvals.store'), [
                'approvable_type' => 'invalid_type',
                'approvable_id' => (string) $this->reservationResource->id,
                'decision' => 'approved',
                'step' => 1,
            ])
            ->assertSessionHasErrors('approvable_type');
    });

    test('validation fails for invalid decision', function () {
        asUser($this->resourceManager)
            ->post(route('approvals.store'), [
                'approvable_type' => 'reservation_resource',
                'approvable_id' => (string) $this->reservationResource->id,
                'decision' => 'maybe',
                'step' => 1,
            ])
            ->assertSessionHasErrors('decision');
    });
});

describe('ApprovalController@bulkStore', function () {
    test('resource manager can bulk approve multiple reservation resources', function () {
        // Create a second reservation resource
        $reservation2 = Reservation::factory()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);
        $reservation2->users()->attach($this->user->id);
        $reservation2->resources()->attach($this->resource->id, [
            'quantity' => 1,
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'state' => 'created',
        ]);

        $reservationResource2 = ReservationResource::query()
            ->where('reservation_id', $reservation2->id)
            ->where('resource_id', $this->resource->id)
            ->first();

        asUser($this->resourceManager)
            ->post(route('approvals.bulkStore'), [
                'approvable_type' => 'reservation_resource',
                'approvable_ids' => [
                    (string) $this->reservationResource->id,
                    (string) $reservationResource2->id,
                ],
                'decision' => 'approved',
                'step' => 1,
            ])
            ->assertRedirect();

        expect(Approval::where('decision', ApprovalDecision::Approved)->count())->toBe(2);
    });

    test('bulk approve skips resources user cannot approve', function () {
        // Create a resource in a different tenant
        $otherTenant = Tenant::factory()->create();
        $otherResource = Resource::factory()->create([
            'tenant_id' => $otherTenant->id,
            'resource_category_id' => $this->category->id,
        ]);

        $reservation2 = Reservation::factory()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);
        $reservation2->resources()->attach($otherResource->id, [
            'quantity' => 1,
            'start_time' => $reservation2->start_time,
            'end_time' => $reservation2->end_time,
            'state' => 'created',
        ]);

        $otherReservationResource = ReservationResource::query()
            ->where('reservation_id', $reservation2->id)
            ->where('resource_id', $otherResource->id)
            ->first();

        asUser($this->resourceManager)
            ->post(route('approvals.bulkStore'), [
                'approvable_type' => 'reservation_resource',
                'approvable_ids' => [
                    (string) $this->reservationResource->id,
                    (string) $otherReservationResource->id,
                ],
                'decision' => 'approved',
                'step' => 1,
            ])
            ->assertRedirect();

        // Only the first one should be approved (from same tenant)
        expect(Approval::where('decision', ApprovalDecision::Approved)->count())->toBe(1);
    });
});
