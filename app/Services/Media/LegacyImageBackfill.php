<?php

namespace App\Services\Media;

use App\Jobs\Media\BackfillLegacyImagesJob;
use App\Jobs\Media\ReindexImageSearchablesJob;
use Illuminate\Database\Eloquent\Builder;

/**
 * Copies legacy image columns into media: queued jobs for media:migrate-images, or a foreground run.
 *
 * Transitional(legacy-images): remove when media:migrate-images --status shows 0 pending.
 */
class LegacyImageBackfill
{
    public const int CHUNK = 50;

    /**
     * Imports synchronously, for local runs and for retrying what a stopped chain left behind.
     */
    public function runNow(?string $onlyTarget = null, ?int $limit = null, ?callable $progress = null): void
    {
        $processed = false;
        foreach ($this->targets($onlyTarget) as $target) {
            $ids = $this->pendingIds($target, $limit);
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
     * @return array<string, list<BackfillLegacyImagesJob>> keyed by target
     */
    public function importJobs(?string $onlyTarget = null, ?int $limit = null): array
    {
        $jobs = [];

        foreach ($this->targets($onlyTarget) as $target) {
            $jobs[$target] = array_map(
                fn (array $chunk): BackfillLegacyImagesJob => new BackfillLegacyImagesJob($target, $chunk),
                array_chunk($this->pendingIds($target, $limit), self::CHUNK),
            );
        }

        return $jobs;
    }

    /**
     * Newest first, so a limited run covers the records people are most likely to look at.
     *
     * @return list<int|string>
     */
    private function pendingIds(string $target, ?int $limit): array
    {
        /** @var Builder $query */
        $query = LegacyImageTargets::rowsWithoutMedia($target);
        $key = $query->getModel()->getKeyName();

        return $query->when($limit !== null, fn (Builder $query) => $query->orderByDesc($key)->limit($limit))->pluck($key)->all();
    }

    /**
     * @return list<string>
     */
    public function targets(?string $onlyTarget): array
    {
        return array_values(array_filter(
            $onlyTarget === null ? array_keys(LegacyImageTargets::TARGETS) : [$onlyTarget],
            LegacyImageTargets::isAvailable(...),
        ));
    }
}
