<?php

use App\Models\Document;
use App\Models\Institution;
use App\Services\Documents\SharepointDocumentFields;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('maps archive columns onto the document, the date as the Vilnius day', function (): void {
    $institution = Institution::factory()->create(['short_name' => ['lt' => 'VU SA MIF', 'en' => 'VU SR MIF']]);
    $document = new Document(['name' => 'protokolas.pdf']);

    SharepointDocumentFields::apply($document, [
        'Title' => 'Tarybos protokolas',
        'Padalinys' => ['Label' => 'VU SA MIF'],
        'Turinys' => ['Label' => 'Protokolai'],
        'Date' => '2026-09-13T21:00:00Z',
        'Language' => 'Lietuvių',
    ]);

    expect($document->title)->toBe('Tarybos protokolas')
        ->and($document->institution_id)->toBe($institution->id)
        ->and($document->content_type)->toBe('Protokolai')
        ->and($document->document_date->toDateString())->toBe('2026-09-14')
        ->and($document->metadataProblems())->toBe([]);
});

test('a column cleared in SharePoint is cleared here too', function (): void {
    $institution = Institution::factory()->create(['short_name' => ['lt' => 'VU SA MIF', 'en' => 'VU SR MIF']]);
    $document = new Document(['name' => 'a.pdf', 'title' => 'Senas', 'language' => 'Anglų', 'content_type' => 'Protokolai', 'document_date' => '2026-01-01']);
    SharepointDocumentFields::apply($document, ['Padalinys' => ['Label' => 'VU SA MIF']]);

    // Graph omits empty columns, and malformed values count as empty.
    SharepointDocumentFields::apply($document, ['Language' => ['unexpected'], 'Turinys' => 'not-a-term']);

    expect($document->title)->toBe('a.pdf')
        ->and($document->language)->toBeNull()
        ->and($document->content_type)->toBeNull()
        ->and($document->document_date)->toBeNull()
        ->and($document->institution_id)->toBeNull()
        ->and($document->sharepoint_institution_label)->toBeNull()
        ->and($institution->exists)->toBeTrue();
});

test('a padalinys that never came from SharePoint is kept when the column is empty', function (): void {
    $institution = Institution::factory()->create();
    $document = new Document(['name' => 'a.pdf', 'institution_id' => $institution->id]);

    SharepointDocumentFields::apply($document, []);

    expect($document->institution_id)->toBe($institution->id);
});

test('names what a manager should fill in before publishing', function (): void {
    $document = new Document(['name' => 'a.pdf']);

    SharepointDocumentFields::apply($document, ['Padalinys' => ['Label' => 'Neegzistuojantis padalinys']]);

    expect($document->sharepoint_institution_label)->toBe('Neegzistuojantis padalinys')
        ->and($document->metadataProblems())->toBe(['unknown_institution', 'content_type', 'document_date', 'language']);
});
