<?php

use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Migration correctness for 2026_09_04_120000_drop_drifted_block_colour_settings.php.
 * Rows are inserted with raw DB::table()->insert() — the same layer the migration
 * itself reads/writes — rather than through the ContentPart model, so the fixture
 * reflects genuinely "old" stored data untouched by the model's own saving hooks.
 */
pest()->use(RefreshDatabase::class);

function runDropDriftedBlockColourSettingsMigration(): void
{
    (require base_path('database/migrations/2026_09_04_120000_drop_drifted_block_colour_settings.php'))->up();
}

function insertContentPart(string $type, array $jsonContent, ?array $options): int
{
    $content = Content::factory()->create();

    return DB::table('content_parts')->insertGetId([
        'content_id' => $content->id,
        'type' => $type,
        'json_content' => json_encode($jsonContent),
        'options' => $options === null ? null : json_encode($options),
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function optionsOf(int $id): ?array
{
    $raw = DB::table('content_parts')->where('id', $id)->value('options');

    return $raw === null ? null : json_decode($raw, true);
}

function jsonContentOf(int $id): array
{
    return json_decode(DB::table('content_parts')->where('id', $id)->value('json_content'), true);
}

test('drops color, variant, isTitleColored and showIcon from shadcn-card options', function (): void {
    $id = insertContentPart('shadcn-card', ['type' => 'doc'], [
        'variant' => 'soft', 'color' => 'red', 'title' => 'Svarbu', 'isTitleColored' => true, 'showIcon' => true,
    ]);

    runDropDriftedBlockColourSettingsMigration();

    expect(optionsOf($id))->toBe(['title' => 'Svarbu']);
});

test('leaves a shadcn-card with none of the drifted keys untouched', function (): void {
    $id = insertContentPart('shadcn-card', ['type' => 'doc'], ['title' => 'Jau švaru']);

    runDropDriftedBlockColourSettingsMigration();

    expect(optionsOf($id))->toBe(['title' => 'Jau švaru']);
});

test('drops color from number-stat-section options', function (): void {
    $id = insertContentPart('number-stat-section', [], ['title' => 'Statistika', 'color' => 'zinc', 'background' => 'none']);

    runDropDriftedBlockColourSettingsMigration();

    expect(optionsOf($id))->toBe(['title' => 'Statistika', 'background' => 'none']);
});

test('drops color/opacity/rotation from hero imageDecorations', function (): void {
    $id = insertContentPart('hero', ['title' => 'Sveiki'], [
        'variant' => 'split',
        'imageDecorations' => [
            ['type' => 'line', 'position' => 'top-right', 'size' => 'md', 'color' => 'vusa-red', 'opacity' => 60],
            ['type' => 'square', 'position' => 'top-left', 'size' => 'md', 'color' => 'vusa-yellow', 'rotation' => true],
        ],
    ]);

    runDropDriftedBlockColourSettingsMigration();

    expect(optionsOf($id))->toBe([
        'variant' => 'split',
        'imageDecorations' => [
            ['type' => 'line', 'position' => 'top-right', 'size' => 'md'],
            ['type' => 'square', 'position' => 'top-left', 'size' => 'md'],
        ],
    ]);
});

test('drops color/opacity/rotation from a decoration nested inside content-grid columns', function (): void {
    $id = insertContentPart('content-grid', [
        [
            'columns' => [
                [
                    'width' => 'col-span-6',
                    'content' => [
                        'type' => 'image',
                        'value' => '/a.jpg',
                        'decorations' => [
                            ['type' => 'line', 'position' => 'top-right', 'size' => 'lg', 'color' => 'vusa-yellow', 'opacity' => 60],
                        ],
                    ],
                ],
            ],
        ],
    ], ['gap' => 'gap-4']);

    runDropDriftedBlockColourSettingsMigration();

    $decorations = jsonContentOf($id)[0]['columns'][0]['content']['decorations'];
    expect($decorations)->toBe([['type' => 'line', 'position' => 'top-right', 'size' => 'lg']]);
});

test('does not touch a tiptap rcTag mark color attribute inside decoration-bearing content', function (): void {
    // rcTag marks carry their own unrelated `color` attribute, but have no `position`/`size` —
    // the recursive walker must only match the decoration shape, never a tiptap mark.
    $id = insertContentPart('content-grid', [
        [
            'columns' => [
                [
                    'width' => 'col-span-6',
                    'content' => [
                        'type' => 'tiptap',
                        'value' => ['type' => 'doc', 'content' => [
                            ['type' => 'paragraph', 'content' => [
                                ['type' => 'text', 'text' => 'Tag', 'marks' => [['type' => 'rcTag', 'attrs' => ['variant' => 'filled', 'color' => 'yellow']]]],
                            ]],
                        ]],
                        'decorations' => [
                            ['type' => 'line', 'position' => 'top-right', 'size' => 'lg', 'color' => 'vusa-yellow'],
                        ],
                    ],
                ],
            ],
        ],
    ], null);

    runDropDriftedBlockColourSettingsMigration();

    $content = jsonContentOf($id)[0]['columns'][0]['content'];
    $mark = $content['value']['content'][0]['content'][0]['marks'][0];
    expect($mark)->toBe(['type' => 'rcTag', 'attrs' => ['variant' => 'filled', 'color' => 'yellow']])
        ->and($content['decorations'])->toBe([['type' => 'line', 'position' => 'top-right', 'size' => 'lg']]);
});

test('drops per-button color from hero-carousel slides', function (): void {
    $id = insertContentPart('hero-carousel', [
        ['title' => 'Pirma', 'buttons' => [['text' => 'Tapk nariu', 'link' => '/x', 'variant' => 'default', 'color' => 'red']]],
        ['title' => 'Antra', 'buttons' => []],
    ], ['autoplay' => false]);

    runDropDriftedBlockColourSettingsMigration();

    $content = jsonContentOf($id);
    expect($content[0]['buttons'][0])->toBe(['text' => 'Tapk nariu', 'link' => '/x', 'variant' => 'default'])
        ->and($content[1]['buttons'])->toBe([]);
});

test('skips a row whose options is malformed JSON instead of crashing', function (): void {
    $content = Content::factory()->create();
    $id = DB::table('content_parts')->insertGetId([
        'content_id' => $content->id,
        'type' => 'shadcn-card',
        'json_content' => json_encode(['type' => 'doc']),
        'options' => '{"color":', // malformed on purpose
        'order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runDropDriftedBlockColourSettingsMigration();

    expect(DB::table('content_parts')->where('id', $id)->value('options'))->toBe('{"color":');
});

test('leaves unrelated block types untouched', function (): void {
    $id = insertContentPart('tiptap', ['type' => 'doc'], null);

    runDropDriftedBlockColourSettingsMigration();

    expect(optionsOf($id))->toBeNull();
});
