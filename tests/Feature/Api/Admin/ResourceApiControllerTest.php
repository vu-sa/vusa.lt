<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenantA = Tenant::query()->first();
    $this->tenantB = Tenant::factory()->create();

    $this->simpleUser = makeUser($this->tenantA);
    $this->manager = makeTenantUserWithRole('Resource Manager', $this->tenantB);

    $this->resource = Resource::factory()->for($this->tenantB)->create([
        'name' => ['lt' => 'Bendra įranga', 'en' => 'Shared equipment'],
    ]);
});

describe('resource preview endpoint', function () {
    test('simple user can preview a resource from another tenant', function () {
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

    test('unauthenticated user cannot preview a resource', function () {
        $this->getJson(route('api.v1.admin.resources.preview', $this->resource))
            ->assertUnauthorized();
    });

    test('preview includes manager contact information', function () {
        $response = asUser($this->simpleUser)
            ->getJson(route('api.v1.admin.resources.preview', $this->resource));

        $response->assertOk();

        $managers = $response->json('data.managers');
        expect($managers)->toHaveCount(1);

        $manager = $managers[0];
        expect($manager)->toHaveKey('id')
            ->toHaveKey('name')
            ->toHaveKey('email')
            ->toHaveKey('phone')
            ->toHaveKey('facebook_url')
            ->toHaveKey('profile_photo_path');

        expect($manager['email'])->toBe($this->manager->email);
        expect($manager['phone'])->toBe($this->manager->phone);
        expect($manager['facebook_url'])->toBe($this->manager->facebook_url);
    });

    test('preview lists previous terminal reservations', function () {
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

    test('preview limits previous reservations to three', function () {
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

    test('preview omits non-terminal past reservations from previous list', function () {
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

    test('preview includes time-ended active reservations in upcoming list', function () {
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
