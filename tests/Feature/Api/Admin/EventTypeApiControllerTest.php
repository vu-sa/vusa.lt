<?php

use App\Models\EventType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->plainUser = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Global Communication Coordinator', $this->tenant);

    $this->eventType = EventType::factory()->create([
        'name' => ['lt' => 'Test Mokymai', 'en' => 'Test Trainings'],
        'slug' => 'test-mokymai-'.uniqid(),
    ]);
});

test('returns 403 to a user without eventType read access', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.eventTypes.index'))
        ->assertForbidden();
});

test('returns paginated eventType collection contract for authorized user', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.eventTypes.index', ['per_page' => 1]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
});

test('filters event types by search query', function (): void {
    $searchKey = 'Konf-'.uniqid();
    EventType::factory()->create([
        'name' => ['lt' => $searchKey, 'en' => 'Conference'],
        'slug' => strtolower($searchKey),
    ]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.eventTypes.index', ['search' => $searchKey]))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.name.lt', $searchKey);
});
