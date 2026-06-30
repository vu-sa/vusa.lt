<?php

use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Tenant;
use App\Services\RelationshipService;
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
            'data' => ['types', 'last_meetings', 'representatives', 'related_institutions'],
        ]);
});

test('preview exposes related institutions with edge metadata', function () {
    $admin = makeAdminUser($this->tenant);

    $relationship = new Relationship([
        'name' => 'Test Relationship',
        'slug' => 'test-relationship',
    ]);
    $relationship->save();

    $target = Institution::factory()->for($this->tenant)->create();

    (new Relationshipable([
        'relationship_id' => $relationship->id,
        'relationshipable_type' => Institution::class,
        'relationshipable_id' => $this->institution->id,
        'related_model_id' => $target->id,
    ]))->save();

    RelationshipService::clearRelatedInstitutionsCache($this->institution->id);

    asUser($admin)
        ->getJson(route('api.v1.admin.institutions.preview', $this->institution))
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'related_institutions' => [
                    ['id', 'name', 'direction', 'type', 'authorized'],
                ],
            ],
        ])
        ->assertJsonPath('data.related_institutions.0.id', (string) $target->id)
        ->assertJsonPath('data.related_institutions.0.direction', 'outgoing')
        ->assertJsonPath('data.related_institutions.0.type', 'direct');
});
