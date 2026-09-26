<?php

use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->first();
    $this->editor = makeTenantUser('Communication Coordinator', $this->tenant);
});

test('lists only soft-deleted records of the viewer\'s tenants, shaped like the search document', function (): void {
    $trashed = Page::factory()->for($this->tenant)->create(['title' => 'Senas puslapis']);
    $trashed->delete();
    Page::factory()->for($this->tenant)->create(['title' => 'Gyvas puslapis']);
    Page::factory()->for($this->otherTenant)->create()->delete();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'pages']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.id', (string) $trashed->id)
        ->assertJsonPath('data.items.0.title', 'Senas puslapis')
        ->assertJsonStructure(['data' => ['items' => [['deleted_at', 'force_delete_blocked_reason']], 'per_page', 'last_page']]);
});

test('searches within the trash', function (): void {
    Page::factory()->for($this->tenant)->create(['title' => 'Stipendijos'])->delete();
    Page::factory()->for($this->tenant)->create(['title' => 'Rinkimai'])->delete();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'pages', 'search' => 'Rinkimai']))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.title', 'Rinkimai');
});

test('refuses a viewer without access to the collection', function (): void {
    asUser(makeUser($this->tenant))
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'pages']))
        ->assertForbidden();
});

test('only answers for allowlisted collections', function (): void {
    asUser($this->editor)
        ->getJson('/api/v1/admin/trash/users')
        ->assertNotFound();
});

test('rejects sorting by an arbitrary column', function (): void {
    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', [
            'collection' => 'pages',
            'sorting' => json_encode([['id' => 'password', 'desc' => true]]),
        ]))
        ->assertUnprocessable();
});
