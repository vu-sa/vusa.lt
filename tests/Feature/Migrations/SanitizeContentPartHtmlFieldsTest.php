<?php

use App\Models\Content;
use App\Models\ContentPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('existing rich fields are cleaned without changing timestamps or plain text', function (): void {
    $part = ContentPart::factory()->create(['content_id' => Content::factory()->create()->id, 'type' => 'hero-carousel', 'json_content' => []]);
    $content = [[
        'title' => '<strong>Sveiki</strong><img src="/photo.png" onload="void(0)">',
        'description' => '<em>Aprašymas</em><img src="/photo.png" onload="void(0)">',
        'subtitle' => 'A < B',
    ]];
    DB::table('content_parts')->where('id', $part->id)->update([
        'json_content' => json_encode($content, JSON_THROW_ON_ERROR),
        'updated_at' => '2026-01-01 12:00:00',
    ]);
    $unchanged = ContentPart::factory()->create([
        'content_id' => $part->content_id, 'type' => 'link-list', 'json_content' => ['title' => 'A < B'],
    ]);
    $unchangedBefore = (array) DB::table('content_parts')->where('id', $unchanged->id)->first();
    $before = (array) DB::table('content_parts')->where('id', $part->id)->first();
    Cache::tags(['pages'])->put('old-page', 'old', 60);
    Cache::tags(['homepage'])->put('old-homepage', 'old', 60);
    Cache::put('unrelated-content', 'keep', 60);
    $migration = require database_path('migrations/2026_11_06_000000_sanitize_content_part_html_fields.php');

    $migration->up();

    $after = (array) DB::table('content_parts')->where('id', $part->id)->first();
    $stored = $part->fresh()->json_content->toArray();
    expect($stored[0]['title'])->toContain('<strong>Sveiki</strong>')->not->toContain('onload')
        ->and($stored[0]['description'])->toContain('<em>Aprašymas</em>')->not->toContain('onload')
        ->and($stored[0]['subtitle'])->toBe('A < B');
    unset($before['json_content'], $after['json_content']);
    expect((array) DB::table('content_parts')->where('id', $unchanged->id)->first())->toBe($unchangedBefore);
    expect($after)->toBe($before)
        ->and(Cache::tags(['pages'])->get('old-page'))->toBeNull()
        ->and(Cache::tags(['homepage'])->get('old-homepage'))->toBeNull()
        ->and(Cache::get('unrelated-content'))->toBe('keep');

    $migration->up();

    expect($part->fresh()->json_content->toArray())->toBe($stored);
});
