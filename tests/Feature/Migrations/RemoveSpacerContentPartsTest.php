<?php

use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('removes spacer content parts without affecting neighbouring blocks', function (): void {
    $content = Content::factory()->create();

    $textId = DB::table('content_parts')->insertGetId([
        'content_id' => $content->id,
        'type' => 'tiptap',
        'json_content' => json_encode(['type' => 'doc']),
        'options' => null,
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('content_parts')->insert([
        'content_id' => $content->id,
        'type' => 'spacer',
        'json_content' => json_encode([]),
        'options' => json_encode(['size' => 'lg']),
        'order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (require base_path('database/migrations/2026_09_05_170000_remove_spacer_content_parts.php'))->up();

    expect(DB::table('content_parts')->where('id', $textId)->exists())->toBeTrue()
        ->and(DB::table('content_parts')->where('type', 'spacer')->exists())->toBeFalse();
});
