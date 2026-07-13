<?php

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
});
