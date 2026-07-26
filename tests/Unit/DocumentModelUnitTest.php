<?php

use App\Models\Document;

test('document calculates in effect status correctly', function () {
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

test('document should be searchable only when it has anonymous url', function () {
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
