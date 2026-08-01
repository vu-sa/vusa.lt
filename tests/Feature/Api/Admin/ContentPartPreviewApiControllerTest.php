<?php

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->user = makeUser($this->tenant);
});

function previewPayload(array $overrides = []): array
{
    return array_merge([
        'tenant_id' => null,
        'locale' => 'lt',
        // `tiptap` is a safe default: always a valid ContentPartEnum case, and it has
        // no resolver registered, so most tests can override just what they're
        // actually exercising without also needing a resolvable type.
        'parts' => [
            ['key' => 'a', 'type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'options' => null],
        ],
    ], $overrides);
}

test('unauthenticated request is rejected', function (): void {
    $this->postJson(route('api.v1.admin.contentParts.preview'), previewPayload(['tenant_id' => $this->tenant->id]))
        ->assertStatus(401);
});

test('rejects a tenant_id the user cannot act for', function (): void {
    $otherTenant = Tenant::factory()->create();

    asUser($this->admin)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload(['tenant_id' => $otherTenant->id]))
        ->assertStatus(403);
});

test('rejects a user with no manageable tenants at all', function (): void {
    asUser($this->user)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload(['tenant_id' => $this->tenant->id]))
        ->assertStatus(403);
});

test('rejects a missing tenant_id with a validation error, not a 403', function (): void {
    asUser($this->admin)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload(['tenant_id' => null]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('tenant_id');
});

test('returns null for a type with no registered resolver, without erroring the whole batch', function (): void {
    asUser($this->admin)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload([
            'tenant_id' => $this->tenant->id,
            'parts' => [
                ['key' => 'text', 'type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'options' => null],
            ],
        ]))
        ->assertStatus(200)
        ->assertJsonPath('data.resolved.text', null);
});

test('rejects an invalid content part type', function (): void {
    asUser($this->admin)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload([
            'tenant_id' => $this->tenant->id,
            'parts' => [
                ['key' => 'a', 'type' => 'not-a-real-type', 'json_content' => [], 'options' => null],
            ],
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('parts.0.type');
});

test('resolves a news block through the same resolver public rendering uses', function (): void {
    News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay(), 'title' => 'Preview news']);

    asUser($this->admin)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload([
            'tenant_id' => $this->tenant->id,
            'parts' => [
                ['key' => 'block-a', 'type' => 'news', 'json_content' => ['title' => ''], 'options' => null],
            ],
        ]))
        ->assertStatus(200)
        ->assertJson(fn ($json) => $json
            ->where('success', true)
            ->where('data.resolved.block-a.type', 'news')
            ->where('data.resolved.block-a.items.0.title', 'Preview news')
            ->etc()
        );
});

test('batches multiple resolvable blocks in a single request', function (): void {
    News::factory()->for($this->tenant)->create(['lang' => 'lt', 'draft' => false, 'publish_time' => now()->subDay()]);

    asUser($this->admin)
        ->postJson(route('api.v1.admin.contentParts.preview'), previewPayload([
            'tenant_id' => $this->tenant->id,
            'parts' => [
                ['key' => 'a', 'type' => 'news', 'json_content' => ['title' => ''], 'options' => null],
                ['key' => 'b', 'type' => 'calendar', 'json_content' => ['title' => ''], 'options' => ['allTenants' => false]],
            ],
        ]))
        ->assertStatus(200)
        ->assertJson(fn ($json) => $json
            ->where('data.resolved.a.type', 'news')
            ->where('data.resolved.b.type', 'calendar')
            ->etc()
        );
});
