<?php

use App\Models\Content;
use App\Models\ContentPart;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('stored rich fields keep formatting and omit unsupported attributes', function (string $type, array $content, string $path): void {
    $part = ContentPart::factory()->create(['content_id' => Content::factory()->create()->id, 'type' => $type, 'json_content' => $content]);
    $stored = data_get($part->fresh()->json_content->toArray(), $path);

    expect($stored)->toContain('<strong>Tekstas</strong>')->not->toContain('onload');

    data_set($content, $path, '<em>Atnaujinta</em><img src="/photo.png" onload="void(0)">');
    $part->update(['json_content' => $content]);

    expect(data_get($part->fresh()->json_content->toArray(), $path))
        ->toContain('<em>Atnaujinta</em>')->not->toContain('onload');
})->with([
    'hero title' => ['hero', ['title' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">'], 'title'],
    'hero description' => ['hero', ['description' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">'], 'description'],
    'carousel title' => ['hero-carousel', [['title' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">']], '0.title'],
    'carousel description' => ['hero-carousel', [['description' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">']], '0.description'],
    'slide deck description' => ['carousel-slide-deck', [['description' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">']], '0.description'],
    'accordion body' => ['shadcn-accordion', [['html' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">']], '0.html'],
    'person quote body' => ['person-quote', ['html' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">'], 'html'],
    'step title' => ['process-steps', [['title' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">']], '0.title'],
    'step text' => ['process-steps', [['text' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">']], '0.text'],
]);

test('plain text and structured editor documents remain unchanged', function (): void {
    $document = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'A < B & C']]]]];
    $content = [
        ['title' => 'A < B & C', 'description' => $document, 'badge' => '<2026>'],
        ['title' => 'Second', 'description' => '<strong>Tekstas</strong><img src="/photo.png" onload="void(0)">'],
    ];
    $part = ContentPart::factory()->create(['content_id' => Content::factory()->create()->id, 'type' => 'carousel-slide-deck', 'json_content' => $content]);
    $stored = $part->fresh()->json_content->toArray();

    expect($stored[0])->toBe($content[0])
        ->and($stored[1]['description'])->toContain('<strong>Tekstas</strong>')->not->toContain('onload');
});
