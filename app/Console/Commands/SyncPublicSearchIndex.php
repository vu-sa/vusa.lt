<?php

namespace App\Console\Commands;

use App\Models\PublicNews;
use App\Models\PublicPage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Keep the public news/pages Typesense indexes in sync with publish_time.
 *
 * News::saved() and Page::saved() push a record into the public index as soon as
 * it is saved — but a scheduled article's shouldBeSearchable() only flips from
 * false to true once its publish_time passes, and nothing re-saves the model when
 * that clock ticks over. Without this sweep, scheduled news/pages would never
 * appear in public search until someone happened to edit them again.
 *
 * Bidirectional and idempotent: also pulls back out anything whose publish_time
 * was pushed into the future, or that was drafted/deactivated via a mass
 * `update()` call, which fires no model events and so never reaches the model
 * hooks in the first place.
 */
#[Description('Sync the public news/pages search index with records whose publish status has changed')]
#[Signature('search:sync-public')]
class SyncPublicSearchIndex extends Command
{
    /**
     * Candidates are windowed rather than scanned in full: anything whose
     * publish_time recently crossed (or is about to) the "is it live" boundary,
     * plus anything edited recently in case a mass update bypassed the model
     * events. Wide enough to self-heal after a day of scheduler downtime,
     * narrow enough to keep each run to a handful of rows.
     */
    private const WINDOW_HOURS = 24;

    public function handle(): int
    {
        $newsSynced = $this->sync(PublicNews::query()->with('tenant'));
        $pagesSynced = $this->sync(PublicPage::query()->with(['tenant', 'category']));

        $this->info("Synced {$newsSynced} news and {$pagesSynced} page(s) with the public search index.");

        return self::SUCCESS;
    }

    /**
     * @param  Builder<PublicNews>|Builder<PublicPage>  $query
     */
    private function sync(Builder $query): int
    {
        $synced = 0;

        $query->where(function (Builder $q): void {
            $q->whereBetween('publish_time', [now()->subHours(self::WINDOW_HOURS), now()->addHours(self::WINDOW_HOURS)])
                ->orWhere('updated_at', '>=', now()->subHours(self::WINDOW_HOURS));
        })->chunkById(200, function ($models) use (&$synced): void {
            [$in, $out] = $models->partition(fn ($model) => $model->shouldBeSearchable());

            $in->searchable();
            $out->unsearchable();

            $synced += $models->count();
        });

        return $synced;
    }
}
