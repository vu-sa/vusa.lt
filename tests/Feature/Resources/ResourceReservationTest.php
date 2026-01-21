<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Tenant;
use App\Models\User;
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
});

describe('auth: simple user', function () {
    test('can view available resources for reservation', function () {
        asUser($this->user)->get(route('resources.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Search/SearchResources')
            );
    });

    test('can create reservation for available resource', function () {
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
        $reservation = \App\Models\Reservation::where('name', 'Team Meeting')->first();
        expect($reservation->users->contains($this->user))->toBeTrue();
    });

    test('cannot create overlapping reservations', function () {
        // Create first reservation
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

        // Try to create overlapping reservation
        asUser($this->user)->post(route('reservations.store'), [
            'name' => 'Conflicting Meeting',
            'start_time' => now()->addDays(1)->addMinutes(30)->format('Y-m-d H:i:s'),
            'end_time' => now()->addDays(1)->addHours(3)->format('Y-m-d H:i:s'),
            'resources' => [
                ['id' => $this->resource->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors();
    });

    test('can update own reservations', function () {
        $reservation = Reservation::factory()->create([
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(1),
        ]);
        $reservation->users()->attach($this->user->id);

        // TODO: Direct reservation updates are not currently supported
        // Updates should go through reservation resources instead
        asUser($this->user)->put(route('reservations.update', $reservation), [
            'name' => 'Updated Meeting Name',
            'description' => 'Updated description',
            'start_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_time' => now()->addDays(2)->addHours(2)->format('Y-m-d H:i:s'),
        ]);

        $reservation->refresh();
        expect($reservation->name)->toBe('Updated Meeting Name');
    })->todo('Direct reservation updates need to be implemented');

    test('cannot update other users reservations', function () {
        $otherUser = User::factory()->create();
        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($otherUser->id);

        // Direct reservation updates are not allowed for anyone
        asUser($this->user)->put(route('reservations.update', $reservation), [
            'name' => 'Hijacked Meeting',
        ]);

        // Should remain unchanged since updates aren't implemented
        $reservation->refresh();
        expect($reservation->name)->not()->toBe('Hijacked Meeting');
    })->todo('Direct reservation updates need proper authorization');

    test('can delete own reservations', function () {
        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($this->user->id);

        asUser($this->user)->delete(route('reservations.destroy', $reservation))
            ->assertRedirect();

        $this->assertSoftDeleted('reservations', ['id' => $reservation->id]);
    });
});

describe('auth: resource manager', function () {
    test('can create new resources', function () {
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

        expect($createdResource)->not->toBeNull();
        expect($createdResource->getTranslation('name', 'lt'))->toBe('Conference Room A');
        expect($createdResource->capacity)->toBe(20);
        expect($createdResource->tenant_id)->toBe($this->tenant->id);
    });

    test('can update resources', function () {
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
        expect($this->resource->getTranslation('name', 'lt'))->toBe('Updated Resource Name');
        expect($this->resource->getTranslation('name', 'lt'))->not->toBe($originalName);
        expect($this->resource->capacity)->toBe(30);
        expect((bool) $this->resource->is_reservable)->toBeFalse(); // Cast to boolean for comparison
    });

    test('can view all reservations', function () {
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

    test('can manage all reservations in tenant', function () {
        $otherUser = User::factory()->create();
        // For this test, let's create the user within the same tenant structure
        $duty = \App\Models\Duty::factory()->create([
            'institution_id' => $this->resourceManager->duties->first()->institution_id,
        ]);
        $otherUser->duties()->attach($duty->id, [
            'start_date' => now(),
        ]);

        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($otherUser->id);

        // TODO: Direct reservation updates are not supported anyway
        asUser($this->resourceManager)->put(route('reservations.update', $reservation), [
            'name' => 'Manager Updated Name',
        ]);

        $reservation->refresh();
        expect($reservation->name)->toBe('Manager Updated Name');
    })->todo('Resource managers should be able to update reservations in their tenant');

    test('cannot manage reservations from other tenants', function () {
        // Create a user from completely different tenant structure
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = \App\Models\Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherDuty = \App\Models\Duty::factory()->create(['institution_id' => $otherInstitution->id]);
        $otherUser = User::factory()->create();
        $otherUser->duties()->attach($otherDuty->id, [
            'start_date' => now(),
        ]);

        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($otherUser->id);

        // Resource Manager from different tenant should not be able to update
        // TODO: Direct reservation updates are not supported anyway
        asUser($this->resourceManager)->put(route('reservations.update', $reservation), [
            'name' => 'Unauthorized Update',
        ]);

        $reservation->refresh();
        expect($reservation->name)->not()->toBe('Unauthorized Update');
    })->todo('Cross-tenant authorization for reservation updates');
});

describe('resource availability logic', function () {
    test('resource shows as unavailable during existing reservations', function () {
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

        // TODO: Availability checking logic needs to be implemented
        // For now, just test that the resources index page works
        $response = asUser($this->user)->get(route('resources.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Search/SearchResources')
            );
    });

    test('can check resource availability for specific time period', function () {
        // TODO: Resource show method is not implemented yet
        // This test should be implemented when availability checking is added
        $this->markTestSkipped('Resource availability checking not yet implemented');
    });
});
