<?php

use App\Enums\DocumentStatus;
use App\Models\Document;

test('document calculates in effect status correctly', function (): void {
    // Document with no validity dates - should return null
    $document = new Document([
        'effective_date' => null,
        'expiration_date' => null,
    ]);

    expect($document->calculateIsInEffect())->toBeNull();

    // Document currently in effect
    $document = new Document([
        'effective_date' => now()->subWeek(),
        'expiration_date' => now()->addWeek(),
    ]);

    expect($document->calculateIsInEffect())->toBeTrue();

    // Document not yet effective
    $document = new Document([
        'effective_date' => now()->addWeek(),
        'expiration_date' => now()->addMonth(),
    ]);

    expect($document->calculateIsInEffect())->toBeFalse();

    // Document expired
    $document = new Document([
        'effective_date' => now()->subMonth(),
        'expiration_date' => now()->subWeek(),
    ]);

    expect($document->calculateIsInEffect())->toBeFalse();
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
