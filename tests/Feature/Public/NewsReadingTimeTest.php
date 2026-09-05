<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * `News::readingTimeMinutes()` — a feature test rather than a unit one because it walks the
 * article's `Content` relation to reach the tiptap blocks, and `ContentPart::$html` is derived
 * from stored `json_content` rather than settable in isolation.
 */
function makeArticleWithWords(int $words): News
{
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $content = Content::factory()->create();

    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => trim(str_repeat('žodis ', $words))]],
            ]],
        ],
    ]);

    return News::factory()->for($tenant)->create(['content_id' => $content->id, 'short' => '']);
}

test('rounds up to whole minutes at 200 words per minute', function (): void {
    expect(makeArticleWithWords(400)->readingTimeMinutes())->toBe(2)
        ->and(makeArticleWithWords(401)->readingTimeMinutes())->toBe(3);
});

/** "0 min read" reads as an error rather than as "this is short". */
test('never reports less than a minute, even for an empty article', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $content = Content::factory()->create();
    $news = News::factory()->for($tenant)->create(['content_id' => $content->id, 'short' => '']);

    expect($news->readingTimeMinutes())->toBe(1);
});

/**
 * `str_word_count()` decides what a word character is from the C locale, so it splits Lithuanian
 * words apart at every ą/č/ę/ū and roughly doubles the count. The implementation splits on
 * whitespace instead; this is the guard.
 */
test('counts a diacritic-heavy Lithuanian word as one word', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $content = Content::factory()->create();

    ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                // 200 of these is exactly one minute — unless each is counted as several words.
                'content' => [['type' => 'text', 'text' => trim(str_repeat('sąžiningumą ', 200))]],
            ]],
        ],
    ]);

    $news = News::factory()->for($tenant)->create(['content_id' => $content->id, 'short' => '']);

    expect($news->readingTimeMinutes())->toBe(1);
});
