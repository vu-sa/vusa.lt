<?php

namespace App\Services\Media;

use App\Jobs\Media\AlignExistingMediaJob;
use App\Jobs\Media\BackfillLegacyImagesJob;
use App\Jobs\Media\ReindexImageSearchablesJob;
use App\Models\Calendar;
use App\Models\Resource;
use App\Models\SupportRequest;
use App\Support\MorphMap;
use Illuminate\Support\Facades\Bus;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Queues the move of legacy image columns into media. Dispatching only plucks ids, so the
 * migration that calls it stays inside the deploy's maintenance window.
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
class LegacyImageBackfill
{
    public const int CHUNK = 50;

    /** Staging re-runs this every night on restored data; a sample exercises every path. */
    public const int STAGING_SAMPLE = 200;

    /**
     * One chain: aligning existing media first frees the disk space Calendar's responsive images
     * held, then the imports, then one reindex. Rows fail individually inside a link, so the
     * chain only stops on something fatal; --status then shows what is left to re-queue.
     */
    public function dispatch(?string $onlyTarget = null, ?callable $progress = null): int
    {
        $imports = $this->importJobs($onlyTarget, $progress);
        $chain = [
            ...($onlyTarget === null ? $this->alignJobs() : []),
            ...$imports,
        ];

        if ($chain === []) {
            return 0;
        }

        if ($imports !== []) {
            $chain[] = new ReindexImageSearchablesJob;
        }

        Bus::chain($chain)->onConnection('long-running')->dispatch();

        return count($imports);
    }

    /**
     * Imports synchronously, for local runs and for retrying what a stopped chain left behind.
     */
    public function runNow(?string $onlyTarget = null, ?int $limit = null, ?callable $progress = null): void
    {
        $processed = false;
        foreach ($this->targets($onlyTarget) as $target) {
            $query = LegacyImageTargets::rowsWithoutMedia($target);
            $ids = $query->when($limit !== null, fn ($query) => $query->limit($limit))->pluck($query->getModel()->getKeyName())->all();
            $checked = 0;
            $total = count($ids);
            if ($progress !== null) {
                $progress($target, 0, $total, null, null, null);
            }

            foreach (array_chunk($ids, self::CHUNK) as $chunk) {
                $processed = true;
                (new BackfillLegacyImagesJob($target, $chunk))->handle(app(LegacyImageImporter::class), $progress === null ? null : function ($id, $value, $outcome) use ($progress, $target, $total, &$checked): void {
                    $progress($target, ++$checked, $total, $id, $value, $outcome);
                });
            }
        }
        if ($processed) {
            (new ReindexImageSearchablesJob)->handle();
        }
    }

    /**
     * Per target: rows holding a value, rows with media, and rows still without media grouped by
     * what the resolver makes of them. Release 2 may drop the columns once `resolved` is 0.
     *
     * @return array<string, array{with_value: int, with_media: int, without_media: array<string, int>}>
     */
    public function status(?string $onlyTarget = null, ?callable $progress = null): array
    {
        $resolver = app(LegacyImageResolver::class);
        $status = [];

        foreach ($this->targets($onlyTarget) as $target) {
            $column = LegacyImageTargets::urlColumn($target);
            $withoutMedia = [];

            $query = LegacyImageTargets::rowsWithoutMedia($target);
            $checked = 0;
            $total = $progress === null ? 0 : $query->count();
            if ($progress !== null) {
                $progress($target, 0, $total, null, null, null);
            }

            $query->select($query->getModel()->getKeyName(), $column)
                ->lazyById(500)
                ->each(function ($owner) use ($resolver, $column, $progress, $target, $total, &$checked, &$withoutMedia): void {
                    $value = $owner->getAttribute($column);
                    $outcome = $resolver->resolve($value)->outcome;
                    $withoutMedia[$outcome] = ($withoutMedia[$outcome] ?? 0) + 1;
                    if ($progress !== null) {
                        $progress($target, ++$checked, $total, $owner->getKey(), $value, $outcome);
                    }
                });

            $withValue = LegacyImageTargets::rowsWithValue($target)->count();

            $status[$target] = [
                'with_value' => $withValue,
                'with_media' => $withValue - array_sum($withoutMedia),
                'without_media' => $withoutMedia,
            ];
        }

        return $status;
    }

    /**
     * @return list<BackfillLegacyImagesJob>
     */
    private function importJobs(?string $onlyTarget, ?callable $progress): array
    {
        $jobs = [];

        foreach ($this->targets($onlyTarget) as $target) {
            $query = LegacyImageTargets::rowsWithoutMedia($target);
            $key = $query->getModel()->getKeyName();

            if (config('app.env') === 'staging') {
                $query->orderByDesc($key)->limit(static::STAGING_SAMPLE);
            }

            $ids = $query->pluck($key)->all();
            if ($progress !== null) {
                $progress($target, count($ids), (int) ceil(count($ids) / self::CHUNK));
            }
            foreach (array_chunk($ids, self::CHUNK) as $chunk) {
                $jobs[] = new BackfillLegacyImagesJob($target, $chunk);
            }
        }

        return $jobs;
    }

    /**
     * @return list<AlignExistingMediaJob>
     */
    private function alignJobs(): array
    {
        $ids = Media::query()
            ->whereIn('model_type', array_map(MorphMap::alias(...), [Calendar::class, Resource::class, SupportRequest::class]))
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return array_map(fn (array $chunk) => new AlignExistingMediaJob($chunk), array_chunk($ids, self::CHUNK));
    }

    /**
     * @return list<string>
     */
    private function targets(?string $onlyTarget): array
    {
        return $onlyTarget === null ? array_keys(LegacyImageTargets::TARGETS) : [$onlyTarget];
    }
}
