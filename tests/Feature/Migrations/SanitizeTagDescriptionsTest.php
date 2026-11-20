<?php

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('stored tag descriptions are cleaned without touching clean rows or timestamps', function (): void {
    $dirty = Tag::factory()->create();
    DB::table('tags')->where('id', $dirty->id)->update([
        'description' => json_encode([
            'lt' => '<p>Tema <strong>svarbi</strong></p><img src="/a.png" onerror="alert(1)">',
            'en' => '<p>Topic</p>',
        ], JSON_THROW_ON_ERROR),
        'updated_at' => '2026-01-01 12:00:00',
    ]);
    $clean = Tag::factory()->create(['description' => ['lt' => '<p>Tvarkinga</p>', 'en' => '<p>Clean</p>']]);
    $cleanBefore = (array) DB::table('tags')->where('id', $clean->id)->first();
    $migration = require database_path('migrations/2026_11_21_000000_sanitize_tag_descriptions.php');

    $migration->up();

    $stored = $dirty->fresh();
    expect($stored->getTranslation('description', 'lt'))->toContain('<strong>svarbi</strong>')->not->toContain('onerror')
        ->and($stored->getTranslation('description', 'en'))->toBe('<p>Topic</p>')
        ->and(DB::table('tags')->where('id', $dirty->id)->value('updated_at'))->toBe('2026-01-01 12:00:00')
        ->and((array) DB::table('tags')->where('id', $clean->id)->first())->toBe($cleanBefore);
});
