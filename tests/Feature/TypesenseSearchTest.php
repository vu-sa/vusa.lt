<?php

namespace Tests\Feature;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use App\Services\Typesense\TypesenseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TypesenseSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function typesense_configuration_is_available()
    {
        // Skip this test if Typesense is not configured in the test environment
        if (! TypesenseManager::isConfigured()) {
            $this->markTestSkipped('Typesense is not configured in test environment');
        }

        $config = TypesenseManager::getFrontendConfig();
        $this->assertNotEmpty($config);
        $this->assertArrayHasKey('apiKey', $config);
        $this->assertArrayHasKey('nodes', $config);
    }

    #[Test]
    public function searchable_models_have_proper_configuration()
    {
        // Test News model
        $news = News::factory()->create([
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);
        $this->assertTrue($news->shouldBeSearchable());
        $this->assertIsArray($news->toSearchableArray());

        // Test Page model
        $page = Page::factory()->create(['is_active' => true]);
        $this->assertTrue($page->shouldBeSearchable());
        $this->assertIsArray($page->toSearchableArray());

        // Test Document model
        $document = Document::factory()->active()->create();
        $this->assertTrue($document->shouldBeSearchable());
        $this->assertIsArray($document->toSearchableArray());

        // Test Calendar model
        $calendar = Calendar::factory()->create(['is_draft' => false]);
        $this->assertTrue($calendar->shouldBeSearchable());
        $this->assertIsArray($calendar->toSearchableArray());
    }

    #[Test]
    public function search_arrays_contain_required_fields()
    {
        $news = News::factory()->create([
            'draft' => false,
            'publish_time' => now()->subHour(),
        ]);
        $searchArray = $news->toSearchableArray();

        $this->assertArrayHasKey('id', $searchArray);
        $this->assertArrayHasKey('title', $searchArray);
        $this->assertArrayHasKey('lang', $searchArray);

        $document = Document::factory()->active()->create();
        $docSearchArray = $document->toSearchableArray();

        $this->assertArrayHasKey('id', $docSearchArray);
        $this->assertArrayHasKey('title', $docSearchArray);
        $this->assertArrayHasKey('language', $docSearchArray);
    }

    #[Test]
    public function draft_models_are_not_searchable()
    {
        // Test that draft news is not searchable
        $draftNews = News::factory()->create(['draft' => true]);
        $this->assertFalse($draftNews->shouldBeSearchable());

        // Test that future news is not searchable
        $futureNews = News::factory()->create([
            'draft' => false,
            'publish_time' => now()->addDay(),
        ]);
        $this->assertFalse($futureNews->shouldBeSearchable());

        // Test that draft pages are not searchable
        $draftPage = Page::factory()->create(['is_active' => false]);
        $this->assertFalse($draftPage->shouldBeSearchable());

        // Test that inactive documents are not searchable
        $inactiveDocument = Document::factory()->create([
            'is_active' => false,
            'anonymous_url' => null, // This makes it not searchable
        ]);
        $this->assertFalse($inactiveDocument->shouldBeSearchable());

        // Test that draft calendar events are not searchable
        $draftCalendar = Calendar::factory()->create(['is_draft' => true]);
        $this->assertFalse($draftCalendar->shouldBeSearchable());
    }
}
