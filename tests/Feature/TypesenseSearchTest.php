<?php

use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicNews;
use App\Models\PublicPage;
use App\Models\User;
use App\Services\Typesense\TypesenseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('typesense configuration is available', function (): void {
    // Configure Typesense settings for this test
    config([
        'scout.typesense.client-settings.api_key' => 'test-api-key-123',
        'scout.typesense.client-settings.search_only_key' => 'test-search-key',
        'scout.typesense.client-settings.nodes' => [
            [
                'host' => 'localhost',
                'port' => '8108',
                'protocol' => 'http',
                'path' => '',
            ],
        ],
    ]);

    $config = TypesenseManager::getFrontendConfig();
    expect($config)->not()->toBeEmpty()
        ->and($config)->toHaveKey('apiKey')
        ->and($config)->toHaveKey('nodes')
        ->and($config['apiKey'])->toBe('test-search-key');
});

test('typesense manager detects proper configuration', function (): void {
    // With proper API key, should be configured
    config(['scout.typesense.client-settings.api_key' => 'test-api-key-123']);
    expect(TypesenseManager::isConfigured())->toBeTrue();

    // With empty API key, should not be configured
    config(['scout.typesense.client-settings.api_key' => '']);
    expect(TypesenseManager::isConfigured())->toBeFalse();

    // With null API key, should not be configured
    config(['scout.typesense.client-settings.api_key' => null]);
    expect(TypesenseManager::isConfigured())->toBeFalse();
});

test('searchable models have proper configuration', function (): void {
    // Test News model searchability logic
    $news = News::factory()->create([
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);
    expect($news->shouldBeSearchable())->toBeTrue()
        ->and($news->toSearchableArray())->toBeArray();

    // Test Page model searchability logic
    $page = Page::factory()->create(['is_active' => true]);
    expect($page->shouldBeSearchable())->toBeTrue()
        ->and($page->toSearchableArray())->toBeArray();

    // Test Document model searchability logic
    $document = Document::factory()->active()->create();
    expect($document->shouldBeSearchable())->toBeTrue()
        ->and($document->toSearchableArray())->toBeArray();

    // Test Calendar model searchability logic
    $calendar = Calendar::factory()->create(['is_draft' => false]);
    expect($calendar->shouldBeSearchable())->toBeTrue()
        ->and($calendar->toSearchableArray())->toBeArray();
});

test('search arrays contain required fields', function (): void {
    $news = News::factory()->create([
        'draft' => false,
        'publish_time' => now()->subHour(),
    ]);
    $searchArray = $news->toSearchableArray();

    expect($searchArray)->toHaveKey('id')
        ->and($searchArray)->toHaveKey('title')
        ->and($searchArray)->toHaveKey('lang');

    $document = Document::factory()->active()->create();
    $docSearchArray = $document->toSearchableArray();

    expect($docSearchArray)->toHaveKey('id')
        ->and($docSearchArray)->toHaveKey('title')
        ->and($docSearchArray)->toHaveKey('language')
        ->and($docSearchArray)->toHaveKey('content_type')
        ->and($docSearchArray)->toHaveKey('tenant_shortname');
});

test('draft/inactive documents and calendar events are not searchable', function (): void {
    // Test that inactive documents are not searchable
    $inactiveDocument = Document::factory()->create([
        'is_active' => false,
        'anonymous_url' => null, // This makes it not searchable
    ]);
    expect($inactiveDocument->shouldBeSearchable())->toBeFalse();

    // Test that draft calendar events are not searchable
    $draftCalendar = Calendar::factory()->create(['is_draft' => true]);
    expect($draftCalendar->shouldBeSearchable())->toBeFalse();
});

test('the admin news index includes drafts and scheduled articles', function (): void {
    // News (admin index) covers everything non-trashed, regardless of publish status —
    // that's what makes drafts/scheduled articles findable in admin search and the
    // "other language" picker. PublicNews carries the actual publication gating.
    $published = News::factory()->create(['draft' => false, 'publish_time' => now()->subHour()]);
    $draft = News::factory()->create(['draft' => true]);
    $scheduled = News::factory()->create(['draft' => false, 'publish_time' => now()->addDay()]);

    expect($published->shouldBeSearchable())->toBeTrue()
        ->and($draft->shouldBeSearchable())->toBeTrue()
        ->and($scheduled->shouldBeSearchable())->toBeTrue();
});

test('the public news index excludes drafts and scheduled articles', function (): void {
    $published = News::factory()->create(['draft' => false, 'publish_time' => now()->subHour()]);
    $draft = News::factory()->create(['draft' => true]);
    $scheduled = News::factory()->create(['draft' => false, 'publish_time' => now()->addDay()]);

    expect(PublicNews::find($published->id)->shouldBeSearchable())->toBeTrue()
        ->and(PublicNews::find($draft->id)->shouldBeSearchable())->toBeFalse()
        ->and(PublicNews::find($scheduled->id)->shouldBeSearchable())->toBeFalse();
});

test('the admin pages index includes inactive and scheduled pages', function (): void {
    $active = Page::factory()->create(['is_active' => true]);
    $inactive = Page::factory()->create(['is_active' => false]);
    $scheduled = Page::factory()->create(['is_active' => true, 'publish_time' => now()->addDay()]);

    expect($active->shouldBeSearchable())->toBeTrue()
        ->and($inactive->shouldBeSearchable())->toBeTrue()
        ->and($scheduled->shouldBeSearchable())->toBeTrue();
});

test('the public pages index excludes inactive and scheduled pages', function (): void {
    $active = Page::factory()->create(['is_active' => true]);
    $inactive = Page::factory()->create(['is_active' => false]);
    $scheduled = Page::factory()->create(['is_active' => true, 'publish_time' => now()->addDay()]);

    expect(PublicPage::find($active->id)->shouldBeSearchable())->toBeTrue()
        ->and(PublicPage::find($inactive->id)->shouldBeSearchable())->toBeFalse()
        ->and(PublicPage::find($scheduled->id)->shouldBeSearchable())->toBeFalse();
});

test('duty search array carries index-aligned member ids for current and previous members', function (): void {
    $duty = Duty::factory()->create();

    $current = User::factory()->create(['name' => 'Current Member']);
    $previous = User::factory()->create(['name' => 'Previous Member']);

    $current->duties()->attach($duty->id, ['start_date' => now()->subMonth(), 'end_date' => null]);
    $previous->duties()->attach($duty->id, ['start_date' => now()->subYear(), 'end_date' => now()->subMonth()]);

    $array = $duty->fresh()->toSearchableArray();

    expect($array)->toHaveKey('current_user_names')
        ->and($array)->toHaveKey('current_user_ids')
        ->and($array)->toHaveKey('previous_user_names')
        ->and($array)->toHaveKey('previous_user_ids')
        ->and($array['current_user_names'])->toContain('Current Member')
        ->and($array['current_user_ids'])->toContain((string) $current->id)
        // Names and ids share the same index so the detail pane can zip them into links.
        ->and($array['current_user_ids'])->toHaveSameSize($array['current_user_names'])
        ->and($array['previous_user_ids'])->toContain((string) $previous->id);
});

test('user search array carries current and previous duties with aligned ids', function (): void {
    $user = User::factory()->create();

    $currentDuty = Duty::factory()->create();
    $previousDuty = Duty::factory()->create();

    $user->duties()->attach($currentDuty->id, ['start_date' => now()->subMonth(), 'end_date' => null]);
    $user->duties()->attach($previousDuty->id, ['start_date' => now()->subYear(), 'end_date' => now()->subMonth()]);

    $array = $user->fresh()->toSearchableArray();

    expect($array)->toHaveKey('current_duty_names')
        ->and($array)->toHaveKey('current_duty_ids')
        ->and($array)->toHaveKey('previous_duty_names')
        ->and($array)->toHaveKey('previous_duty_ids')
        ->and($array['current_duty_ids'])->toContain((string) $currentDuty->id)
        ->and($array['current_duty_ids'])->toHaveSameSize($array['current_duty_names'])
        ->and($array['previous_duty_ids'])->toContain((string) $previousDuty->id)
        // The current duty must not leak into the previous-duty buckets.
        ->and($array['previous_duty_ids'])->not->toContain((string) $currentDuty->id);
});
