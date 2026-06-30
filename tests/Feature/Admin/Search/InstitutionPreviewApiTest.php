<?php

use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

test('unauthenticated request is rejected', function () {
    $this->getJson(route('api.v1.admin.institutions.preview', $this->institution))
        ->assertStatus(401);
});

test('user without access to the institution gets 403', function () {
    $user = makeUser($this->tenant);

    asUser($user)
        ->getJson(route('api.v1.admin.institutions.preview', $this->institution))
        ->assertStatus(403);
});

test('authorized admin receives the preview payload', function () {
    $admin = makeAdminUser($this->tenant);

    asUser($admin)
        ->getJson(route('api.v1.admin.institutions.preview', $this->institution))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['types', 'last_meetings', 'representatives'],
        ]);
});
