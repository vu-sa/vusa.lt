<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = makeAdminUser(Tenant::query()->first());
    $this->tenant = Tenant::factory()->create(['type' => 'padalinys']);
    $this->institution = Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Tiesioginė institucija', 'en' => 'Direct institution'],
    ]);
});

test('timeline requires an authenticated user', function () {
    $this->getJson(route('api.v1.admin.visak.timeline', [
        'tenant_ids' => [$this->tenant->id],
    ]))->assertUnauthorized();
});

test('timeline rejects tenants outside the visible scope', function () {
    $user = makeUser(Tenant::factory()->create(['type' => 'padalinys']));

    asUser($user)
        ->getJson(route('api.v1.admin.visak.timeline', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertForbidden();
});

test('timeline returns direct institutions, cross-tenant relations, and summaries', function () {
    $relatedTenant = Tenant::factory()->create(['type' => 'padalinys']);
    $relatedInstitution = Institution::factory()->for($relatedTenant)->create([
        'name' => ['lt' => 'Susijusi institucija', 'en' => 'Related institution'],
    ]);
    $relationship = Relationship::query()->create([
        'name' => 'Test relationship',
        'slug' => 'test-relationship',
    ]);

    Relationshipable::query()->create([
        'relationship_id' => $relationship->id,
        'relationshipable_type' => Institution::class,
        'relationshipable_id' => $this->institution->id,
        'related_model_id' => $relatedInstitution->id,
    ]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.timeline', [
            'tenant_ids' => [$this->tenant->id],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.institution_summary.all', 1)
        ->assertJsonPath('data.institutions.0.id', $this->institution->id)
        ->assertJsonPath('data.related_institutions.0.id', $relatedInstitution->id)
        ->assertJsonPath('data.related_institutions.0.is_related', true);
});

test('representatives are searched and paginated without loading the full list', function () {
    $duty = Duty::factory()->for($this->institution)->create();

    collect([
        ['name' => 'First Representative', 'email' => 'first-representative@example.test'],
        ['name' => 'Second Representative', 'email' => 'second-representative@example.test'],
        ['name' => 'Third Representative', 'email' => 'third-representative@example.test'],
    ])->each(function (array $attributes) use ($duty) {
        $representative = User::factory()->create($attributes);
        $representative->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);
    });

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.representatives', [
            'tenant_ids' => [$this->tenant->id],
            'per_page' => 2,
        ]))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.users')
        ->assertJsonPath('data.pagination.total', 3)
        ->assertJsonPath('data.pagination.last_page', 2);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.visak.representatives', [
            'tenant_ids' => [$this->tenant->id],
            'search' => 'second-representative@example.test',
        ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.users')
        ->assertJsonPath('data.users.0.email', 'second-representative@example.test');
});
