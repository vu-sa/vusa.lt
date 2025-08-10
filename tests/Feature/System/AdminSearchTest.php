<?php

use App\Models\Calendar;
use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ModelIndexer;
use Illuminate\Support\Facades\Context;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = makeTenantUser('Communication Coordinator');
    $this->tenant = $this->user->institutions()->first()?->tenant;
});

describe('ModelIndexer with CustomAdminSearchBuilder', function () {
    test('admin searches include inactive pages', function () {
        // Create active and inactive pages
        $activePage = Page::factory()->create([
            'title' => 'Active Page',
            'is_active' => true,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        $inactivePage = Page::factory()->create([
            'title' => 'Inactive Page',
            'is_active' => false,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        // Simulate admin request
        request()->merge(['text' => '']);

        // Login user for authorization
        asUser($this->user);

        // Create ModelIndexer for Page model
        $indexer = new ModelIndexer(Page::class);
        $results = $indexer->builder->get();

        // Admin should see both active and inactive pages
        expect($results->count())->toBeGreaterThanOrEqual(2)
            ->and($results->contains('id', $activePage->id))->toBeTrue()
            ->and($results->contains('id', $inactivePage->id))->toBeTrue();
    });

    test('admin searches include draft news', function () {
        // Create published and draft news
        $publishedNews = News::factory()->create([
            'title' => 'Published News',
            'draft' => false,
            'publish_time' => now()->subHour(),
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        $draftNews = News::factory()->create([
            'title' => 'Draft News',
            'draft' => true,
            'publish_time' => now()->subHour(),
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        request()->merge(['text' => '']);
        asUser($this->user);

        $indexer = new ModelIndexer(News::class);
        $results = $indexer->builder->get();

        // Admin should see both published and draft news
        expect($results->contains('id', $publishedNews->id))->toBeTrue()
            ->and($results->contains('id', $draftNews->id))->toBeTrue();
    });

    test('admin searches include draft calendar events', function () {
        // Create published and draft calendar events
        $publishedEvent = Calendar::factory()->create([
            'title' => ['lt' => 'Published Event', 'en' => 'Published Event'],
            'is_draft' => false,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        $draftEvent = Calendar::factory()->create([
            'title' => ['lt' => 'Draft Event', 'en' => 'Draft Event'],
            'is_draft' => true,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        request()->merge(['text' => '']);
        asUser($this->user);

        $indexer = new ModelIndexer(Calendar::class);
        $results = $indexer->builder->get();

        // Admin should see both published and draft events
        expect($results->contains('id', $publishedEvent->id))->toBeTrue()
            ->and($results->contains('id', $draftEvent->id))->toBeTrue();
    });

    test('admin searches include private documents', function () {
        // Get user's institution for proper authorization
        $institution = $this->user->institutions()->first();

        // Skip test if no institution available (complex setup required)
        if (! $institution) {
            $this->markTestSkipped('User has no institution - complex Document authorization setup required');
        }

        // Create public and private documents with proper institution
        $publicDocument = Document::factory()->create([
            'title' => 'Public Document',
            'anonymous_url' => 'https://example.com/public-doc',
            'institution_id' => $institution->id,
        ]);

        $privateDocument = Document::factory()->create([
            'title' => 'Private Document',
            'anonymous_url' => null, // No public access
            'institution_id' => $institution->id,
        ]);

        request()->merge(['text' => '']);
        asUser($this->user);

        $indexer = new ModelIndexer(Document::class);
        $results = $indexer->builder->get();

        // Admin should see both public and private documents
        expect($results->count())->toBeGreaterThanOrEqual(2)
            ->and($results->contains('id', $publicDocument->id))->toBeTrue()
            ->and($results->contains('id', $privateDocument->id))->toBeTrue();
    });

    test('admin search works with search terms', function () {
        // Create pages with specific titles
        $matchingPage = Page::factory()->create([
            'title' => 'Special Admin Content',
            'is_active' => false, // Inactive but should be found by admin
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        $nonMatchingPage = Page::factory()->create([
            'title' => 'Regular Content',
            'is_active' => true,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        // Search for "Special"
        request()->merge(['text' => 'Special']);
        asUser($this->user);

        $indexer = new ModelIndexer(Page::class);
        $results = $indexer->builder->get();

        // Should find the matching page even though it's inactive
        expect($results->contains('id', $matchingPage->id))->toBeTrue()
            ->and($results->contains('id', $nonMatchingPage->id))->toBeFalse();
    });

    test('admin context is properly set', function () {
        // Verify that the Context is being set correctly
        request()->merge(['text' => '']);
        asUser($this->user);

        $indexer = new ModelIndexer(Page::class);

        // The Context should be set to 'admin' during the search
        expect(Context::get('search_context'))->toBe('admin');
    });
});

describe('Public Scout search behavior', function () {
    test('public search excludes inactive pages', function () {
        // Create active and inactive pages
        $activePage = Page::factory()->create([
            'title' => 'Active Page',
            'is_active' => true,
        ]);

        $inactivePage = Page::factory()->create([
            'title' => 'Inactive Page',
            'is_active' => false,
        ]);

        // Use Scout search directly (simulating public search)
        config(['scout.driver' => 'database']);
        $results = Page::search('')->get();

        // Public search should only include active pages
        expect($results->contains('id', $activePage->id))->toBeTrue()
            ->and($results->contains('id', $inactivePage->id))->toBeFalse();
    });

    test('public search excludes draft news', function () {
        // Create published and draft news
        $publishedNews = News::factory()->create([
            'title' => 'Published News',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $draftNews = News::factory()->create([
            'title' => 'Draft News',
            'draft' => true,
            'publish_time' => now()->subHour(),
        ]);

        config(['scout.driver' => 'database']);
        $results = News::search('')->get();

        // Public search should only include published news
        expect($results->contains('id', $publishedNews->id))->toBeTrue()
            ->and($results->contains('id', $draftNews->id))->toBeFalse();
    });

    test('public search excludes future news', function () {
        // Create current and future news
        $currentNews = News::factory()->create([
            'title' => 'Current News',
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);

        $futureNews = News::factory()->create([
            'title' => 'Future News',
            'draft' => false,
            'publish_time' => now()->addDay(),
        ]);

        config(['scout.driver' => 'database']);
        $results = News::search('')->get();

        // Public search should only include current news
        expect($results->contains('id', $currentNews->id))->toBeTrue()
            ->and($results->contains('id', $futureNews->id))->toBeFalse();
    });

    test('public search excludes draft calendar events', function () {
        // Create published and draft events
        $publishedEvent = Calendar::factory()->create([
            'title' => ['lt' => 'Published Event', 'en' => 'Published Event'],
            'is_draft' => false,
        ]);

        $draftEvent = Calendar::factory()->create([
            'title' => ['lt' => 'Draft Event', 'en' => 'Draft Event'],
            'is_draft' => true,
        ]);

        config(['scout.driver' => 'database']);
        $results = Calendar::search('')->get();

        // Public search should only include published events
        expect($results->contains('id', $publishedEvent->id))->toBeTrue()
            ->and($results->contains('id', $draftEvent->id))->toBeFalse();
    });

    test('public search excludes private documents', function () {
        // Create public and private documents
        $publicDocument = Document::factory()->create([
            'title' => 'Public Document',
            'anonymous_url' => 'https://example.com/public-doc',
        ]);

        $privateDocument = Document::factory()->create([
            'title' => 'Private Document',
            'anonymous_url' => null,
        ]);

        // Set public search context (default behavior - no admin context)
        \Illuminate\Support\Facades\Context::forget('search_context');

        config(['scout.driver' => 'database']);
        $results = Document::search('')->get();

        // Public search should only include public documents
        expect($results->contains('id', $publicDocument->id))->toBeTrue()
            ->and($results->contains('id', $privateDocument->id))->toBeFalse();
    });
});

describe('shouldBeSearchable behavior consistency', function () {
    test('inactive pages are not searchable via Scout', function () {
        // Create inactive page
        $inactivePage = Page::factory()->create(['is_active' => false]);

        // Should not be searchable for public Scout search
        expect($inactivePage->shouldBeSearchable())->toBeFalse();
    });

    test('draft news is not searchable via Scout', function () {
        $draftNews = News::factory()->create(['draft' => true]);

        // Should not be searchable for public Scout search
        expect($draftNews->shouldBeSearchable())->toBeFalse();
    });

    test('private documents are not searchable via Scout', function () {
        $privateDocument = Document::factory()->create(['anonymous_url' => null]);

        // Should not be searchable for public Scout search
        expect($privateDocument->shouldBeSearchable())->toBeFalse();
    });

    test('draft calendar events are not searchable via Scout', function () {
        $draftEvent = Calendar::factory()->create(['is_draft' => true]);

        // Should not be searchable for public Scout search
        expect($draftEvent->shouldBeSearchable())->toBeFalse();
    });
});

describe('ModelIndexer authorization and filtering', function () {
    test('admin searches respect tenant boundaries', function () {
        // Ensure we have an "other" tenant
        $otherTenant = Tenant::factory()->create();

        // Create pages for different tenants
        $ownTenantPage = Page::factory()->create([
            'title' => 'Own Tenant Page',
            'is_active' => false,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        $otherTenantPage = Page::factory()->create([
            'title' => 'Other Tenant Page',
            'is_active' => false,
            'tenant_id' => $otherTenant->id, // Use properly created tenant
        ]);

        request()->merge(['text' => '']);
        asUser($this->user);

        $indexer = new ModelIndexer(Page::class);
        $results = $indexer->builder->get();

        // Should only see pages from own tenant
        expect($results->contains('id', $ownTenantPage->id))->toBeTrue()
            ->and($results->contains('id', $otherTenantPage->id))->toBeFalse();
    });

    test('admin searches work with filters', function () {
        // Create pages with different properties
        $activePage = Page::factory()->create([
            'title' => 'Active Page',
            'is_active' => true,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        $inactivePage = Page::factory()->create([
            'title' => 'Inactive Page',
            'is_active' => false,
            'tenant_id' => $this->tenant?->id ?? 1,
        ]);

        // Test with filter for only active pages
        request()->merge([
            'text' => '',
            'filters' => json_encode(['is_active' => [true]]),
        ]);
        asUser($this->user);

        $indexer = new ModelIndexer(Page::class);
        $indexer->filterAllColumns();
        $results = $indexer->builder->get();

        // Should only include active pages when filtered
        expect($results->contains('id', $activePage->id))->toBeTrue()
            ->and($results->contains('id', $inactivePage->id))->toBeFalse();
    });
});
