<?php

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

test('document should be searchable only when it has anonymous url', function (): void {
    // Document without anonymous URL should not be searchable (not public)
    $document = new Document(['anonymous_url' => null]);
    expect($document->shouldBeSearchable())->toBeFalse();

    $document = new Document(['anonymous_url' => '']);
    expect($document->shouldBeSearchable())->toBeFalse();

    // Document with anonymous URL should be searchable (public)
    $document = new Document([
        'anonymous_url' => 'https://sharepoint.com/public/document',
    ]);
    expect($document->shouldBeSearchable())->toBeTrue();
});

test('isUrlShortcut detects .url files case-insensitively', function (): void {
    expect(new Document(['name' => 'ataskaita2023.vusa.lt.url'])->isUrlShortcut())->toBeTrue()
        ->and(new Document(['name' => 'ataskaita2023.vusa.lt.URL'])->isUrlShortcut())->toBeTrue()
        ->and(new Document(['name' => 'protokolas.pdf'])->isUrlShortcut())->toBeFalse()
        ->and(new Document(['name' => null])->isUrlShortcut())->toBeFalse();
});
