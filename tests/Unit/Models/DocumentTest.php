<?php

use App\Enums\DocumentStatus;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

test('document content type category is determined correctly', function (): void {
    $document = Document::factory()->create(['content_type' => 'VU SA P Nutarimas']);
    expect($document->toSearchableArray()['content_type_category'])->toBe('VU SA P');

    $document = Document::factory()->create(['content_type' => 'VU SA Rekomendacija']);
    expect($document->toSearchableArray()['content_type_category'])->toBe('VU SA');

    $document = Document::factory()->create(['content_type' => 'Kitas dokumentas']);
    expect($document->toSearchableArray()['content_type_category'])->toBe('Kita');

    $document = Document::factory()->create(['content_type' => null]);
    expect($document->toSearchableArray()['content_type_category'])->toBe('Kita');
});

test('document language code is standardized', function (): void {
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

test('document date range bucket is calculated accurately across all boundaries', function (): void {
    Carbon::setTestNow('2026-06-15 12:00:00');

    try {
        $cases = [
            ['date' => null, 'expected' => 'unknown'],
            ['date' => now()->addDays(10), 'expected' => 'recent_1month'],
            ['date' => now()->subDays(15), 'expected' => 'recent_1month'],
            ['date' => now()->subMonth(), 'expected' => 'recent_1month'],
            ['date' => now()->subMonth()->subDay(), 'expected' => 'recent_3months'],
            ['date' => now()->subMonths(3), 'expected' => 'recent_3months'],
            ['date' => now()->subMonths(3)->subDay(), 'expected' => 'recent_6months'],
            ['date' => now()->subMonths(6), 'expected' => 'recent_6months'],
            ['date' => now()->subMonths(6)->subDay(), 'expected' => 'recent_1year'],
            ['date' => now()->subMonths(12), 'expected' => 'recent_1year'],
            ['date' => now()->subMonths(12)->subDay(), 'expected' => 'recent_2years'],
            ['date' => now()->subMonths(18), 'expected' => 'recent_2years'],
            ['date' => now()->subMonths(24), 'expected' => 'recent_2years'],
            ['date' => now()->subMonths(24)->subDay(), 'expected' => 'older'],
        ];

        foreach ($cases as $case) {
            $document = Document::factory()->create(['document_date' => $case['date']]);

            expect($document->toSearchableArray()['date_range_bucket'])
                ->toBe($case['expected']);
        }
    } finally {
        Carbon::setTestNow();
    }
});

test('toSearchableArray includes link_url', function (): void {
    $document = Document::factory()->create([
        'name' => 'ataskaita2023.vusa.lt.url',
        'anonymous_url' => 'https://sharepoint.example.com/document/123',
        'link_url' => 'https://ataskaita2023.vusa.lt',
    ]);

    expect($document->toSearchableArray())->toHaveKey('link_url', 'https://ataskaita2023.vusa.lt');
});

test('toSearchableArray includes only published meeting calendar events', function (): void {
    $meeting = Meeting::factory()->create();
    $document = Document::factory()->create(['meeting_id' => $meeting->id]);
    $event = Calendar::factory()->create(['meeting_id' => $meeting->id, 'is_draft' => false]);

    expect($document->toSearchableArray()['calendar_event_id'])->toBe($event->id);

    $event->update(['is_draft' => true]);

    expect($document->fresh()->toSearchableArray()['calendar_event_id'])->toBeNull();
});

test('document calculates in effect status correctly', function (): void {
    Carbon::setTestNow('2026-06-15 12:00:00');

    try {
        $document = new Document([
            'effective_date' => null,
            'expiration_date' => null,
        ]);

        expect($document->calculateIsInEffect())->toBeNull();

        $document = new Document([
            'effective_date' => now()->subWeek(),
            'expiration_date' => now()->addWeek(),
        ]);

        expect($document->calculateIsInEffect())->toBeTrue();

        $document = new Document([
            'effective_date' => now()->addWeek(),
            'expiration_date' => now()->addMonth(),
        ]);

        expect($document->calculateIsInEffect())->toBeFalse();

        $document = new Document([
            'effective_date' => now()->subMonth(),
            'expiration_date' => now()->subWeek(),
        ]);

        expect($document->calculateIsInEffect())->toBeFalse();
    } finally {
        Carbon::setTestNow();
    }
});

test('document should be searchable only when published with an anonymous url', function (): void {
    $published = ['status' => DocumentStatus::Published];

    expect(new Document([...$published, 'anonymous_url' => null])->shouldBeSearchable())->toBeFalse()
        ->and(new Document([...$published, 'anonymous_url' => ''])->shouldBeSearchable())->toBeFalse()
        ->and(new Document([...$published, 'anonymous_url' => 'https://sharepoint.com/public/document'])->shouldBeSearchable())->toBeTrue()
        // A pending file never reaches the shared search collection, even if a link exists.
        ->and(new Document(['status' => DocumentStatus::Pending, 'anonymous_url' => 'https://sharepoint.com/public/document'])->shouldBeSearchable())->toBeFalse();
});

test('isUrlShortcut detects .url files case-insensitively', function (): void {
    expect(new Document(['name' => 'ataskaita2023.vusa.lt.url'])->isUrlShortcut())->toBeTrue()
        ->and(new Document(['name' => 'ataskaita2023.vusa.lt.URL'])->isUrlShortcut())->toBeTrue()
        ->and(new Document(['name' => 'protokolas.pdf'])->isUrlShortcut())->toBeFalse()
        ->and(new Document(['name' => null])->isUrlShortcut())->toBeFalse();
});
