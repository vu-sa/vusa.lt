<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantA = Tenant::query()->first();
    $this->tenantB = Tenant::factory()->create();

    $this->simpleUser = makeUser($this->tenantA);
    $this->manager = makeTenantUserWithRole('Resource Manager', $this->tenantB);

    $this->resource = Resource::factory()->for($this->tenantB)->create([
        'name' => ['lt' => 'Bendra įranga', 'en' => 'Shared equipment'],
    ]);
});

describe('resource preview endpoint', function (): void {
    test('simple user can preview a resource from another tenant', function (): void {
        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'upcoming_reservations',
                    'previous_reservations',
                    'managers',
                ],
            ]);
    });

    test('unauthenticated user cannot preview a resource', function (): void {
        $this->getJson(route('api.v1.admin.resources.preview', $this->resource))
            ->assertUnauthorized();
    });

    test('preview includes manager contact information', function (): void {
        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk();

        $managers = $response->json('data.managers');
        expect($managers)->toHaveCount(1);

        $manager = $managers[0];
        expect($manager)->toHaveKeys(['id', 'name', 'email', 'phone', 'facebook_url', 'profile_photo_path'])
            ->toMatchArray(['email' => $this->manager->email, 'phone' => $this->manager->phone, 'facebook_url' => $this->manager->facebook_url]);
    });

    test('preview lists previous terminal reservations', function (): void {
        $reservation = Reservation::factory()->create(['name' => 'Returned event']);
        $this->resource->reservations()->attach($reservation->id, [
            'quantity' => 1,
            'state' => 'returned',
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(2),
        ]);

        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk()
            ->assertJsonPath('data.previous_reservations.0.name', 'Returned event')
            ->assertJsonPath('data.previous_reservations.0.quantity', 1)
            ->assertJsonPath('data.previous_reservations.0.state', 'returned');
    });

    test('preview limits previous reservations to three', function (): void {
        for ($i = 0; $i < 5; $i++) {
            $reservation = Reservation::factory()->create(['name' => "Past event {$i}"]);
            $this->resource->reservations()->attach($reservation->id, [
                'quantity' => 1,
                'state' => 'returned',
                'start_time' => now()->subDays($i + 3),
                'end_time' => now()->subDays($i + 2),
            ]);
        }

        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk();
        expect($response->json('data.previous_reservations'))->toHaveCount(3);
    });

    test('preview omits non-terminal past reservations from previous list', function (): void {
        $reservation = Reservation::factory()->create(['name' => 'Stale active event']);
        $this->resource->reservations()->attach($reservation->id, [
            'quantity' => 1,
            'state' => 'reserved',
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(2),
        ]);

        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk()
            ->assertJsonPath('data.previous_reservations', []);
    });

    test('preview includes time-ended active reservations in upcoming list', function (): void {
        $reservation = Reservation::factory()->create(['name' => 'Stale active event']);
        $this->resource->reservations()->attach($reservation->id, [
            'quantity' => 1,
            'state' => 'reserved',
            'start_time' => now()->subDays(3),
            'end_time' => now()->subDays(2),
        ]);

        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk()
            ->assertJsonPath('data.upcoming_reservations.0.name', 'Stale active event')
            ->assertJsonPath('data.upcoming_reservations.0.state', 'reserved');
    });
});
