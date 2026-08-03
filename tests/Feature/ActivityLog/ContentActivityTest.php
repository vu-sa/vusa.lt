<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Services\ContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function tiptapPartData(?int $id, string $html): array
{
    return [
        'id' => $id,
        'type' => 'tiptap',
        'json_content' => (new Editor)->setContent($html)->getDocument(),
        'options' => null,
    ];
}

/**
 * Round-trips a part's own current content back into ContentService input
 * shape, unmodified -- for reorder-only scenarios, where the point is that
 * NOTHING but `order` differs, so json_content must not be regenerated with
 * different (randomised) text.
 *
 * @return array<string, mixed>
 */
function unchangedPartData(ContentPart $part): array
{
    return [
        'id' => $part->id,
        'type' => $part->type,
        'json_content' => $part->json_content->toArray(),
        'options' => $part->options?->toArray(),
    ];
}

/**
 * ContentPartFactory doesn't set `order` (production always assigns it
 * explicitly via ContentService), so `Content::factory()->hasParts($n)`
 * leaves every part at the column default of 0 -- useless for reorder
 * scenarios, which need a known starting sequence to detect a change against.
 */
function createContentWithOrderedParts(int $count): Content
{
    $content = Content::factory()->create();

    foreach (range(0, $count - 1) as $i) {
        ContentPart::factory()->for($content, 'content')->create(['order' => $i]);
    }

    return $content->fresh('parts');
}

test('editing a content part logs content_summary, not the raw json_content', function (): void {
    $news = News::factory()->create();
    $part = $news->content->parts->first();

    Activity::query()->delete();

    $part->update(['json_content' => (new Editor)->setContent('<p>Updated body text</p>')->getDocument()]);

    $activity = Activity::where('subject_type', ContentPart::class)->where('subject_id', $part->id)->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('updated')
        ->and(data_get($activity, 'attribute_changes.attributes'))->toHaveKey('content_summary')
        ->and(data_get($activity, 'attribute_changes.attributes'))->not->toHaveKey('json_content')
        ->and(data_get($activity, 'attribute_changes.attributes.content_summary'))->toContain('Updated body text');
});

test('a long block body is truncated to 500 characters in content_summary', function (): void {
    $page = Page::factory()->create();
    $part = $page->content->parts->first();

    Activity::query()->delete();

    $longText = trim(str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit. ', 30));
    $part->update(['json_content' => (new Editor)->setContent("<p>{$longText}</p>")->getDocument()]);

    $activity = Activity::where('subject_type', ContentPart::class)->where('subject_id', $part->id)->latest('id')->first();
    $summary = (string) data_get($activity, 'attribute_changes.attributes.content_summary');

    // Str::limit(…, 500) truncates the content to 500 chars and then appends
    // its '...' marker, so the total is bounded by 500 + strlen('...') rather
    // than exactly 500 -- the point being it's nowhere near the ~1900-char
    // source text, not that it's precisely 500.
    expect(mb_strlen($summary))->toBeLessThanOrEqual(503)
        ->and($summary)->toEndWith('...');
});

test('a pure order-only touch on a content part logs nothing', function (): void {
    $page = Page::factory()->create();
    $part = $page->content->parts->first();

    Activity::query()->delete();

    $part->update(['order' => $part->order + 5]);

    expect(Activity::where('subject_type', ContentPart::class)->where('subject_id', $part->id)->count())->toBe(0);
});

test('removing a content part through ContentService logs a deleted activity', function (): void {
    $content = createContentWithOrderedParts(2);
    Page::factory()->create(['content_id' => $content->id]);
    $parts = $content->parts()->orderBy('order')->get();
    $keptPart = $parts->first();
    $removedId = $parts->last()->id;

    Activity::query()->delete();

    app(ContentService::class)->updateContentParts($content->fresh('parts'), [
        tiptapPartData($keptPart->id, '<p>Still here</p>'),
    ]);

    expect(Activity::where('subject_type', ContentPart::class)->where('subject_id', $removedId)->where('event', 'deleted')->count())->toBe(1)
        ->and($content->fresh('parts')->parts)->toHaveCount(1);
});

test('inserting a block at the front logs one created activity, zero updated activities, and one content_reordered on the page', function (): void {
    $content = createContentWithOrderedParts(5);
    $page = Page::factory()->create(['content_id' => $content->id]);
    $existingParts = $content->parts()->orderBy('order')->get();

    Activity::query()->delete();

    $newContentParts = [
        tiptapPartData(null, '<p>Brand new block</p>'),
        ...$existingParts->map(fn (ContentPart $part) => unchangedPartData($part))->all(),
    ];

    app(ContentService::class)->updateContentParts($content->fresh('parts'), $newContentParts);

    expect(Activity::where('subject_type', ContentPart::class)->where('event', 'created')->count())->toBe(1)
        ->and(Activity::where('subject_type', ContentPart::class)->whereIn('subject_id', $existingParts->pluck('id'))->where('event', 'updated')->count())->toBe(0)
        ->and(Activity::where('subject_type', Page::class)->where('subject_id', $page->id)->where('event', 'content_reordered')->count())->toBe(1);
});

test('a metadata-only save that leaves content parts untouched logs no content_reordered', function (): void {
    $content = createContentWithOrderedParts(3);
    $page = Page::factory()->create(['content_id' => $content->id]);
    $parts = $content->parts()->orderBy('order')->get();

    Activity::query()->delete();

    $sameOrderContentParts = $parts->map(fn (ContentPart $part) => unchangedPartData($part))->all();

    app(ContentService::class)->updateContentParts($content->fresh('parts'), $sameOrderContentParts);

    expect(Activity::where('subject_type', Page::class)->where('subject_id', $page->id)->where('event', 'content_reordered')->count())->toBe(0)
        ->and(Activity::where('subject_type', ContentPart::class)->whereIn('subject_id', $parts->pluck('id'))->count())->toBe(0);
});

test('swapping two blocks logs exactly one content_reordered activity', function (): void {
    $content = createContentWithOrderedParts(3);
    $page = Page::factory()->create(['content_id' => $content->id]);
    $parts = $content->parts()->orderBy('order')->get();

    Activity::query()->delete();

    $swapped = collect([$parts[1], $parts[0], $parts[2]])
        ->map(fn (ContentPart $part) => unchangedPartData($part))
        ->all();

    app(ContentService::class)->updateContentParts($content->fresh('parts'), $swapped);

    expect(Activity::where('subject_type', Page::class)->where('subject_id', $page->id)->where('event', 'content_reordered')->count())->toBe(1);
});
