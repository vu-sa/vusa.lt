<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Models\Category;
use App\Models\EventType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Types every untyped calendar event from a deterministic, ordered set of rules matched
 * against the event's title (both `lt` and `en`), so the "Kita informacija" catch-all the
 * old `categories` colour palette produced stops growing.
 *
 * Rule order matters: rule 3 (konferencija) must precede rule 6 (atstovavimas) — a
 * conference title routinely contains both "konferencij" and "senat"/"komisij". First
 * match wins; a row that matches nothing is left NULL and listed in the report, never
 * guessed at.
 *
 * Idempotent by construction: only events with `event_type_id IS NULL` are considered, so
 * a type set by an admin (or a previous run) is never revisited or overwritten.
 */
#[Description('Backfill calendar.event_type_id from title-matching rules, leaving genuinely ambiguous events untyped.')]
#[Signature('taxonomy:backfill-events
                            {--dry-run : Report proposed changes without writing (default when neither option is given)}
                            {--force : Apply the proposed changes}')]
class BackfillEventTypesCommand extends Command
{
    /**
     * Ordered slug => regex map. First match wins. Patterns are matched case-insensitively
     * against both the Lithuanian and English title.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const array TITLE_RULES = [
        ['stovykla', '/stovykl|camp/iu'],
        ['konferencija', '/konferencij|ataskaitin.{0,3}-rinkimin/iu'],
        ['rinkimai', '/rinkim|kandidat|balsav/iu'],
        ['mokymai', '/mokym|seminar|dirbtuv|workshop|training/iu'],
        ['atstovavimas', '/posėd|susitik|dalyvau|darbo grup|rektorat|senat|komisij/iu'],
        ['terminas', '/registracij|terminas|paraišk|deadline/iu'],
        ['renginys', '/šventė|vakaras|festival|gimtadien|koncert|turnyr/iu'],
    ];

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $dryRun = ! $force;

        $eventTypesBySlug = EventType::query()->pluck('id', 'slug');

        foreach ([...array_column(self::TITLE_RULES, 0), 'posedis'] as $slug) {
            if (! $eventTypesBySlug->has($slug)) {
                $this->error("Missing event type '{$slug}' — run migrations/seeders first.");

                return self::FAILURE;
            }
        }

        $freshmenCampsCategoryId = Category::query()->withTrashed()->where('alias', 'freshmen-camps')->value('id');
        $conferencesCategoryId = Category::query()->withTrashed()->where('alias', 'vu-sa-conferences')->value('id');

        /** @var array<string, int> $buckets */
        $buckets = [];
        $unmatched = [];
        $proposed = [];

        Calendar::query()
            ->whereNull('event_type_id')
            ->chunkById(200, function ($events) use (
                $eventTypesBySlug, $freshmenCampsCategoryId, $conferencesCategoryId,
                &$buckets, &$unmatched, &$proposed
            ): void {
                foreach ($events as $event) {
                    /** @var Calendar $event */
                    $slug = $this->resolveSlug($event, $freshmenCampsCategoryId, $conferencesCategoryId);

                    if ($slug === null) {
                        $unmatched[] = "#{$event->id}: ".$this->titleFor($event);

                        continue;
                    }

                    $buckets[$slug] = ($buckets[$slug] ?? 0) + 1;
                    $proposed[] = [$event->id, $eventTypesBySlug[$slug]];
                }
            });

        $this->reportBuckets($buckets, count($unmatched));

        Storage::disk('local')->put('taxonomy/unmatched-events.txt', implode("\n", $unmatched)."\n");
        $this->line('Unmatched titles written to storage/app/taxonomy/unmatched-events.txt');

        if ($dryRun) {
            $this->info('Dry run — no rows written. Re-run with --force to apply.');

            return self::SUCCESS;
        }

        $applied = 0;

        foreach ($proposed as [$id, $eventTypeId]) {
            Calendar::query()->whereKey($id)->update(['event_type_id' => $eventTypeId]);
            $applied++;
        }

        $this->info("Applied event_type_id to {$applied} event(s).");

        return self::SUCCESS;
    }

    /**
     * First match wins. Rule 1 (a meeting announcement) and rules 2-3 (the two campaign
     * categories) are checked explicitly before falling through to the ordered title-regex
     * table (rules 4-8).
     */
    private function resolveSlug(Calendar $event, ?int $freshmenCampsCategoryId, ?int $conferencesCategoryId): ?string
    {
        if ($event->meeting_id !== null) {
            return 'posedis';
        }

        if ($freshmenCampsCategoryId !== null && $event->category_id === $freshmenCampsCategoryId) {
            return 'stovykla';
        }

        if ($conferencesCategoryId !== null && $event->category_id === $conferencesCategoryId) {
            return 'konferencija';
        }

        $titleLt = $event->getTranslation('title', 'lt', false) ?? '';
        $titleEn = $event->getTranslation('title', 'en', false) ?? '';
        $haystack = $titleLt."\n".$titleEn;

        foreach (self::TITLE_RULES as [$slug, $pattern]) {
            if (preg_match($pattern, $haystack) === 1) {
                return $slug;
            }
        }

        return null;
    }

    private function titleFor(Calendar $event): string
    {
        return $event->getTranslation('title', 'lt', false)
            ?? $event->getTranslation('title', 'en', false)
            ?? '(no title)';
    }

    /**
     * @param  array<string, int>  $buckets
     */
    private function reportBuckets(array $buckets, int $unmatchedCount): void
    {
        $this->info('Backfill bucket summary:');

        foreach ($buckets as $slug => $count) {
            $this->line("  {$slug}: {$count}");
        }

        $this->line("  (unmatched, left NULL): {$unmatchedCount}");
    }
}
