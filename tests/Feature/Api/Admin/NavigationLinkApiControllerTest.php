<?php

use App\Models\Calendar;
use App\Models\Category;
use App\Models\Document;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Global Communication Coordinator', $this->tenant);
});

describe('resolve-url endpoint', function (): void {
    test('resolves a page URL', function (): void {
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'permalink' => 'apie-mus']);

        $response = asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'pages',
                'id' => $page->id,
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        expect($response->json('data.url'))->toContain('apie-mus');
    });

    test('resolves a news URL', function (): void {
        $news = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'permalink' => 'nauja-naujiena']);

        $response = asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'news',
                'id' => $news->id,
            ]);

        $response->assertOk();
        expect($response->json('data.url'))->toContain('nauja-naujiena');
    });

    test('resolves a calendar event URL', function (): void {
        $calendar = Calendar::factory()->for($this->tenant)->create();

        $response = asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'calendar',
                'id' => $calendar->id,
            ]);

        $response->assertOk();
        expect($response->json('data.url'))->toContain((string) $calendar->id);
    });

    test('resolves an institution URL', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();

        $response = asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'institutions',
                'id' => $institution->id,
            ]);

        $response->assertOk();
        expect($response->json('data.url'))->toContain((string) $institution->id);
    });

    test('resolves a document URL from its anonymous_url', function (): void {
        $document = Document::factory()->create(['anonymous_url' => 'https://vusa.lt/files/doc.pdf']);

        $response = asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'documents',
                'id' => $document->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.url', 'https://vusa.lt/files/doc.pdf');
    });

    test('resolves a category URL against www regardless of the current tenant', function (): void {
        $category = Category::factory()->create(['alias' => 'renginiai']);

        $response = asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'categories',
                'id' => $category->id,
            ]);

        $response->assertOk();
        expect($response->json('data.url'))
            ->toContain('renginiai')
            ->toContain('www');
    });

    test('returns 404 for a record that does not exist', function (): void {
        asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'pages',
                'id' => 999999,
            ])
            ->assertNotFound();
    });

    test('rejects an unknown collection', function (): void {
        asUser($this->admin)
            ->postJson(route('api.v1.admin.navigation.resolveUrl'), [
                'collection' => 'meetings',
                'id' => 1,
            ])
            ->assertUnprocessable();
    });

    test('unauthenticated user cannot resolve a URL', function (): void {
        $page = Page::factory()->for($this->tenant)->create();

        $this->postJson(route('api.v1.admin.navigation.resolveUrl'), [
            'collection' => 'pages',
            'id' => $page->id,
        ])->assertUnauthorized();
    });
});
