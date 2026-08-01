<?php

use App\Actions\PairTranslatedRecord;
use App\Models\News;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * `pages.other_lang_id` and `news.other_lang_id` carry a plain UNIQUE index that
 * predates soft deletes, so a trashed row keeps occupying its value invisibly. Every
 * scoped "who holds this id?" query skipped it, so the value was never released and
 * the next write hit a 1062 duplicate-key error.
 */
describe('pairing', function (): void {
    test('links both directions', function (): void {
        $lt = Page::factory()->create(['lang' => 'lt']);
        $en = Page::factory()->create(['lang' => 'en']);

        PairTranslatedRecord::execute($lt, $en->id);

        expect($lt->fresh()->other_lang_id)->toBe($en->id)
            ->and($en->fresh()->other_lang_id)->toBe($lt->id);
    });

    test('unpairing clears both sides', function (): void {
        $lt = Page::factory()->create(['lang' => 'lt']);
        $en = Page::factory()->create(['lang' => 'en']);
        PairTranslatedRecord::execute($lt, $en->id);

        PairTranslatedRecord::execute($lt->fresh(), null);

        expect($lt->fresh()->other_lang_id)->toBeNull()
            ->and($en->fresh()->other_lang_id)->toBeNull();
    });

    test('repointing releases the previous counterpart', function (): void {
        $lt = Page::factory()->create(['lang' => 'lt']);
        $first = Page::factory()->create(['lang' => 'en']);
        $second = Page::factory()->create(['lang' => 'en']);
        PairTranslatedRecord::execute($lt, $first->id);

        PairTranslatedRecord::execute($lt->fresh(), $second->id);

        expect($first->fresh()->other_lang_id)->toBeNull()
            ->and($lt->fresh()->other_lang_id)->toBe($second->id)
            ->and($second->fresh()->other_lang_id)->toBe($lt->id);
    });

    test('a record cannot be paired with itself', function (): void {
        $page = Page::factory()->create();

        expect(fn () => PairTranslatedRecord::execute($page, $page->id))
            ->toThrow(InvalidArgumentException::class);
    });

    test('a deleted counterpart is refused rather than silently nulled', function (): void {
        $page = Page::factory()->create(['lang' => 'lt']);
        $trashed = Page::factory()->create(['lang' => 'en']);
        $trashed->delete();

        expect(fn () => PairTranslatedRecord::execute($page, $trashed->id))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('claiming a value held by a trashed record', function (): void {
    // This is the reported bug: SQLSTATE[23000] 1062 Duplicate entry for
    // pages_other_lang_id_unique.
    test('succeeds and releases the trashed holder', function (): void {
        $en = Page::factory()->create(['lang' => 'en']);
        $oldLt = Page::factory()->create(['lang' => 'lt']);
        PairTranslatedRecord::execute($oldLt, $en->id);

        // Deleting the Lithuanian page leaves it still pointing at $en.
        $oldLt->delete();
        expect(Page::withTrashed()->find($oldLt->id)->other_lang_id)->toBe($en->id);

        $newLt = Page::factory()->create(['lang' => 'lt']);
        PairTranslatedRecord::execute($newLt, $en->id);

        expect($newLt->fresh()->other_lang_id)->toBe($en->id)
            ->and($en->fresh()->other_lang_id)->toBe($newLt->id)
            ->and(Page::withTrashed()->find($oldLt->id)->other_lang_id)->toBeNull();
    });

    test('works for news as well as pages', function (): void {
        $en = News::factory()->create(['lang' => 'en']);
        $oldLt = News::factory()->create(['lang' => 'lt']);
        PairTranslatedRecord::execute($oldLt, $en->id);
        $oldLt->delete();

        $newLt = News::factory()->create(['lang' => 'lt']);
        PairTranslatedRecord::execute($newLt, $en->id);

        expect($newLt->fresh()->other_lang_id)->toBe($en->id)
            ->and(News::withTrashed()->find($oldLt->id)->other_lang_id)->toBeNull();
    });
});

describe('lifecycle', function (): void {
    test('soft delete clears the surviving counterpart so its language switch is not broken', function (): void {
        $lt = Page::factory()->create(['lang' => 'lt']);
        $en = Page::factory()->create(['lang' => 'en']);
        PairTranslatedRecord::execute($lt, $en->id);

        $lt->fresh()->delete();

        expect($en->fresh()->other_lang_id)->toBeNull()
            // The deleted page remembers its partner so restore can re-pair.
            ->and(Page::withTrashed()->find($lt->id)->other_lang_id)->toBe($en->id);
    });

    test('restore re-establishes the pairing when the counterpart is still free', function (): void {
        $lt = Page::factory()->create(['lang' => 'lt']);
        $en = Page::factory()->create(['lang' => 'en']);
        PairTranslatedRecord::execute($lt, $en->id);

        $lt = $lt->fresh();
        $lt->delete();
        $lt->restore();

        expect($lt->fresh()->other_lang_id)->toBe($en->id)
            ->and($en->fresh()->other_lang_id)->toBe($lt->id);
    });

    test('restore leaves the record unpaired when the counterpart was claimed meanwhile', function (): void {
        $lt = Page::factory()->create(['lang' => 'lt']);
        $en = Page::factory()->create(['lang' => 'en']);
        PairTranslatedRecord::execute($lt, $en->id);

        $lt = $lt->fresh();
        $lt->delete();

        $otherLt = Page::factory()->create(['lang' => 'lt']);
        PairTranslatedRecord::execute($otherLt, $en->id);

        $lt->restore();

        expect($lt->fresh()->other_lang_id)->toBeNull()
            ->and($en->fresh()->other_lang_id)->toBe($otherLt->id);
    });
});
