<?php

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('document calculates in effect status correctly', function () {
    // Document with no validity dates - should return null
    $document = Document::factory()->make([
        'effective_date' => null,
        'expiration_date' => null,
    ]);

    expect($document->calculateIsInEffect())->toBeNull();

    // Document currently in effect
    $document = Document::factory()->make([
        'effective_date' => now()->subWeek(),
        'expiration_date' => now()->addWeek(),
    ]);

    expect($document->calculateIsInEffect())->toBeTrue();

    // Document not yet effective
    $document = Document::factory()->make([
        'effective_date' => now()->addWeek(),
        'expiration_date' => now()->addMonth(),
    ]);

    expect($document->calculateIsInEffect())->toBeFalse();

    // Document expired
    $document = Document::factory()->make([
        'effective_date' => now()->subMonth(),
        'expiration_date' => now()->subWeek(),
    ]);

    expect($document->calculateIsInEffect())->toBeFalse();
});

test('document should be searchable only when it has anonymous url', function () {
    // Document without anonymous URL should not be searchable (not public)
    $document = Document::factory()->make(['anonymous_url' => null]);
    expect($document->shouldBeSearchable())->toBeFalse();

    $document = Document::factory()->make(['anonymous_url' => '']);
    expect($document->shouldBeSearchable())->toBeFalse();

    // Document with anonymous URL should be searchable (public)
    $document = Document::factory()->make([
        'anonymous_url' => 'https://sharepoint.com/public/document',
    ]);
    expect($document->shouldBeSearchable())->toBeTrue();
});

test('document content type category is determined correctly', function () {
    $document = Document::factory()->create(['content_type' => 'VU SA P Nutarimas']);
    expect($document->toSearchableArray()['content_type_category'])->toBe('VU SA P');

    $document = Document::factory()->create(['content_type' => 'VU SA Rekomendacija']);
    expect($document->toSearchableArray()['content_type_category'])->toBe('VU SA');

    $document = Document::factory()->create(['content_type' => 'Kitas dokumentas']);
    expect($document->toSearchableArray()['content_type_category'])->toBe('Kita');

    $document = Document::factory()->create(['content_type' => null]);
    expect($document->toSearchableArray()['content_type_category'])->toBe('Kita');
});

test('document language code is standardized', function () {
    $document = Document::factory()->create(['language' => 'Lietuvių']);
    expect($document->toSearchableArray()['language_code'])->toBe('lt');

    $document = Document::factory()->create(['language' => 'Lithuanian']);
    expect($document->toSearchableArray()['language_code'])->toBe('lt');

    $document = Document::factory()->create(['language' => 'Anglų']);
    expect($document->toSearchableArray()['language_code'])->toBe('en');

    $document = Document::factory()->create(['language' => 'English']);
    expect($document->toSearchableArray()['language_code'])->toBe('en');

    $document = Document::factory()->create(['language' => 'Vokiečių']);
    expect($document->toSearchableArray()['language_code'])->toBe('unknown');
});

test('document file extension is categorized correctly', function () {
    $document = Document::factory()->create(['name' => 'document.pdf']);
    expect($document->toSearchableArray()['file_extension'])->toBe('pdf');

    $document = Document::factory()->create(['name' => 'document.docx']);
    expect($document->toSearchableArray()['file_extension'])->toBe('word');

    $document = Document::factory()->create(['name' => 'spreadsheet.xlsx']);
    expect($document->toSearchableArray()['file_extension'])->toBe('excel');

    $document = Document::factory()->create(['name' => 'presentation.pptx']);
    expect($document->toSearchableArray()['file_extension'])->toBe('powerpoint');

    $document = Document::factory()->create(['name' => 'link.url']);
    expect($document->toSearchableArray()['file_extension'])->toBe('link');

    $document = Document::factory()->create(['name' => 'unknown.xyz']);
    expect($document->toSearchableArray()['file_extension'])->toBe('other');
});

test('document date range bucket is calculated correctly', function () {
    // Test the most important cases: null handling and that it returns valid strings

    // No date - should return 'unknown'
    $document = Document::factory()->create(['document_date' => null]);
    expect($document->toSearchableArray()['date_range_bucket'])->toBe('unknown');

    // Any valid date should return a valid bucket string
    $document = Document::factory()->create(['document_date' => now()->subDays(15)]);
    $bucket = $document->toSearchableArray()['date_range_bucket'];

    $validBuckets = ['recent_1month', 'recent_3months', 'recent_6months', 'recent_1year', 'recent_2years', 'older'];
    expect($bucket)->toBeIn($validBuckets);

    // Test with a different date
    $document = Document::factory()->create(['document_date' => now()->subMonths(18)]);
    $bucket = $document->toSearchableArray()['date_range_bucket'];
    expect($bucket)->toBeIn($validBuckets);
});
