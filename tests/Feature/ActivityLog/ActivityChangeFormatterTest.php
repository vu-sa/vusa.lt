<?php

use App\Models\Activity;
use App\Models\AgendaItemNote;
use App\Models\ContentPart;
use App\Models\Meeting;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Services\ActivityChangeFormatter;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

/**
 * Runs the formatter on a single activity and returns its formatted_changes.
 *
 * @return array<int, array<string, mixed>>
 */
function formatSingleActivity(Activity $activity): array
{
    app(ActivityChangeFormatter::class)->prepare(new Collection([$activity]));

    return $activity->formatted_changes;
}

test('a page content-part edit produces a diff row with the full plain-text projection, not a 120-char truncation', function (): void {
    $page = Page::factory()->create();
    $part = $page->content->parts->first();

    $longOld = str_repeat('Sena teksto dalis. ', 20); // ~380 chars, past the old 120-char display cap
    $part->update(['json_content' => (new Editor)->setContent("<p>{$longOld}</p>")->getDocument()]);

    $activity = Activity::where('subject_type', MorphMap::alias(ContentPart::class))->where('subject_id', $part->id)
        ->where('event', 'updated')->latest('id')->first();

    $change = collect(formatSingleActivity($activity))->firstWhere('key', 'content_summary');

    expect($change['type'])->toBe('diff')
        ->and(mb_strlen($change['old_display']))->toBeGreaterThan(120)
        ->and($change['old'])->toBeNull()
        ->and($change['new'])->toBeNull();
});

test('a translatable field logs one diff row per locale that actually changed', function (): void {
    $problem = Problem::factory()->create([
        'description' => ['lt' => 'Nepakitęs LT tekstas', 'en' => 'Original EN text'],
    ]);

    // Only EN changes; LT is set to the exact same value it already has.
    $problem->update(['description' => ['lt' => 'Nepakitęs LT tekstas', 'en' => 'Updated EN text']]);

    $activity = Activity::where('subject_type', MorphMap::alias(Problem::class))->where('subject_id', $problem->id)
        ->where('event', 'updated')->latest('id')->first();

    $changes = collect(formatSingleActivity($activity));

    expect($changes->firstWhere('key', 'description.en'))->not->toBeNull()
        ->and($changes->firstWhere('key', 'description.lt'))->toBeNull()
        ->and($changes->firstWhere('key', 'description'))->toBeNull();
});

test('an EN-only edit under the LT app locale still produces an activity (does not silently drop)', function (): void {
    app()->setLocale('lt');

    $problem = Problem::factory()->create([
        'description' => ['lt' => 'Bendras tekstas', 'en' => 'Original'],
    ]);

    $problem->update(['description' => ['lt' => 'Bendras tekstas', 'en' => 'Changed']]);

    // Before Problem::getActivitylogOptions() logged raw locale-map JSON,
    // resolveAttributeValue() read getAttribute(), which under an LT session
    // returns only the LT string -- unchanged here, so the whole activity
    // would previously have been dropped by dontLogEmptyChanges().
    expect(Activity::where('subject_type', MorphMap::alias(Problem::class))->where('subject_id', $problem->id)
        ->where('event', 'updated')->exists())->toBeTrue();
});

test('a legacy single-locale string value (pre-fix activity rows) still renders as one row', function (): void {
    $problem = Problem::factory()->create();

    $activity = Activity::create([
        'log_name' => 'default',
        'subject_type' => MorphMap::alias(Problem::class),
        'subject_id' => $problem->id,
        'event' => 'updated',
        'description' => 'updated',
        'attribute_changes' => [
            'attributes' => ['description' => 'Nauja reikšmė be JSON'],
            'old' => ['description' => 'Sena reikšmė be JSON'],
        ],
    ]);

    $changes = collect(formatSingleActivity($activity));

    expect($changes->firstWhere('key', 'description'))->not->toBeNull()
        ->and($changes->firstWhere('key', 'description.lt'))->toBeNull()
        ->and($changes->firstWhere('key', 'description')['type'])->toBe('diff')
        ->and($changes->firstWhere('key', 'description')['new_display'])->toBe('Nauja reikšmė be JSON');
});

test('adjacent HTML block tags are separated by a space, not concatenated', function (): void {
    $problem = Problem::factory()->create();

    $activity = Activity::create([
        'log_name' => 'default',
        'subject_type' => MorphMap::alias(Problem::class),
        'subject_id' => $problem->id,
        'event' => 'updated',
        'description' => 'updated',
        'attribute_changes' => [
            'attributes' => ['description' => json_encode(['lt' => '<p>beta</p><li>one</li>', 'en' => 'unchanged'])],
            'old' => ['description' => json_encode(['lt' => '<p>alpha</p>', 'en' => 'unchanged'])],
        ],
    ]);

    $changes = collect(formatSingleActivity($activity));
    $ltChange = $changes->firstWhere('key', 'description.lt');

    expect($ltChange['new_display'])->toBe('beta one')
        ->and($ltChange['new_display'])->not->toContain('betaone');
});

test('a formatting-only edit (unchanged plain text) degrades to the rich placeholder instead of an unhighlighted diff', function (): void {
    $problem = Problem::factory()->create();

    $activity = Activity::create([
        'log_name' => 'default',
        'subject_type' => MorphMap::alias(Problem::class),
        'subject_id' => $problem->id,
        'event' => 'updated',
        'description' => 'updated',
        'attribute_changes' => [
            'attributes' => ['description' => json_encode(['lt' => '<p><strong>Hello world</strong></p>', 'en' => 'Same'])],
            'old' => ['description' => json_encode(['lt' => '<p>Hello world</p>', 'en' => 'Same'])],
        ],
    ]);

    $changes = collect(formatSingleActivity($activity));

    expect($changes->firstWhere('key', 'description.en'))->toBeNull()
        ->and($changes->firstWhere('key', 'description.lt')['type'])->toBe('rich')
        ->and($changes->firstWhere('key', 'description.lt')['old_display'])->toBeNull()
        ->and($changes->firstWhere('key', 'description.lt')['new_display'])->toBeNull()
        ->and($changes->firstWhere('key', 'description.lt')['old'])->toBeNull()
        ->and($changes->firstWhere('key', 'description.lt')['new'])->toBeNull();
});

test('a value past the diff character cap degrades to the rich placeholder', function (): void {
    $problem = Problem::factory()->create();
    $huge = str_repeat('a ', 11000); // ~22000 plain-text chars, past DIFF_CHAR_CAP

    $activity = Activity::create([
        'log_name' => 'default',
        'subject_type' => MorphMap::alias(Problem::class),
        'subject_id' => $problem->id,
        'event' => 'updated',
        'description' => 'updated',
        'attribute_changes' => [
            'attributes' => ['description' => json_encode(['lt' => "<p>{$huge}</p>", 'en' => 'unchanged'])],
            'old' => ['description' => json_encode(['lt' => '<p>short</p>', 'en' => 'unchanged'])],
        ],
    ]);

    $changes = collect(formatSingleActivity($activity));

    expect($changes->firstWhere('key', 'description.lt')['type'])->toBe('rich')
        ->and($changes->firstWhere('key', 'description.lt')['new_display'])->toBeNull();
});

test('AgendaItemNote rich fields still show the flat placeholder, unaffected by the diff change', function (): void {
    $meeting = Meeting::factory()->create();
    $agendaItem = AgendaItem::factory()->for($meeting, 'meeting')->create();
    $note = AgendaItemNote::factory()->for($agendaItem, 'agendaItem')->create(['notes_html' => '<p>Original</p>']);

    $note->update(['notes_html' => '<p>Changed</p>']);

    $activity = Activity::where('subject_type', MorphMap::alias(AgendaItemNote::class))->where('subject_id', $note->id)
        ->where('event', 'updated')->latest('id')->first();

    $change = collect(formatSingleActivity($activity))->firstWhere('key', 'notes_html');

    expect($change['type'])->toBe('rich')
        ->and($change['old_display'])->toBeNull()
        ->and($change['new_display'])->toBeNull();
});
