<?php

namespace App\Support\Dutiables;

use App\Models\Duty;
use Illuminate\Support\Collection;

/**
 * @phpstan-import-type ReviewItem from \App\Actions\Dutiables\PlanExOfficioReview
 * @phpstan-import-type BlockedItem from \App\Actions\Dutiables\PlanExOfficioReview
 */
class ExOfficioReviewDependencies
{
    /**
     * @param  list<ReviewItem>  $items
     * @return array{items: list<ReviewItem>, blocked: list<BlockedItem>}
     */
    public static function resolve(array $items, Collection $rows, Collection $targets): array
    {
        $rows = $rows->keyBy('id');
        $targets = $targets->keyBy('id');
        $dependencies = self::indexDependencies($items);
        $sourcesByTarget = self::sourcesByTarget($rows, $targets);
        foreach ($items as &$item) {
            $item['requires'] = [];
            if ($item['kind'] === 'link') {
                self::requireSources($item, $items, $rows, $sourcesByTarget[$item['target_duty_id']], $dependencies);
            }
        }
        unset($item);

        $blocked = [];
        foreach ($items as $item) {
            if ($item['unavailable_dependency'] ?? false) {
                $blocked[] = self::blocked($item, $rows, $targets);
            }
        }
        $items = array_values(array_filter($items, fn (array $item) => ! ($item['unavailable_dependency'] ?? false)));
        $items = self::removeUnavailable($items, $rows, $targets, $blocked);

        return ['items' => self::deselectUnavailable($items), 'blocked' => $blocked];
    }

    private static function indexDependencies(array $items): array
    {
        $dependencies = [];
        foreach ($items as $index => $item) {
            if (in_array($item['kind'], ['create', 'link'], true)) {
                $dependencies[$item['target_duty_id']][$item['source_id']] ??= $index;
            }
        }

        return $dependencies;
    }

    private static function sourcesByTarget(Collection $rows, Collection $targets): array
    {
        $sources = [];
        foreach ($targets as $target) {
            $sourceDutyIds = $target->exOfficioSourceDuties
                ->filter(fn (Duty $duty): bool => (bool) $duty->institution?->is_active)
                ->pluck('id');
            $sources[$target->id] = $rows->whereIn('duty_id', $sourceDutyIds)
                ->whereNull('via_dutiable_id')
                ->where('ended_by_institution_deactivation', false);
        }

        return $sources;
    }

    private static function requireSources(
        array &$item,
        array $items,
        Collection $rows,
        Collection $sources,
        array $dependencies,
    ): void {
        $candidate = $rows[$item['row_id']];
        foreach ($sources as $source) {
            if ($source->id === $item['source_id'] || $source->dutiable_id !== $candidate->dutiable_id
                || DutiablePeriods::overlapDays($candidate, $source) === 0) {
                continue;
            }
            $index = $dependencies[$item['target_duty_id']][$source->id] ?? null;
            if ($index !== null) {
                $dependency = $items[$index];
                $item['requires'][] = $dependency['id'];
                if (! $dependency['selected']) {
                    $item['selected'] = false;
                }
            } elseif (! $rows->where('duty_id', $item['target_duty_id'])->contains('via_dutiable_id', $source->id)) {
                $item['unavailable_dependency'] = true;
            }
        }
    }

    private static function removeUnavailable(array $items, Collection $rows, Collection $targets, array &$blocked): array
    {
        do {
            $count = count($items);
            $available = array_column($items, 'id');
            $items = array_values(array_filter($items, function (array $item) use ($available, &$blocked, $rows, $targets): bool {
                if (array_diff($item['requires'], $available) === []) {
                    return true;
                }
                $blocked[] = self::blocked($item, $rows, $targets);

                return false;
            }));
        } while (count($items) !== $count);

        return $items;
    }

    private static function deselectUnavailable(array $items): array
    {
        do {
            $changed = false;
            $selected = collect($items)->where('selected', true)->pluck('id')->all();
            foreach ($items as &$item) {
                if ($item['selected'] && array_diff($item['requires'], $selected) !== []) {
                    $item['selected'] = false;
                    $changed = true;
                }
            }
            unset($item);
        } while ($changed);

        return $items;
    }

    /** @return BlockedItem */
    private static function blocked(array $item, Collection $rows, Collection $targets): array
    {
        $row = $rows[$item['row_id']];
        $target = $targets[$item['target_duty_id']];

        return [
            'source_id' => $row->id,
            'holder_name' => $row->user?->name,
            'target_duty_id' => $target->id,
            'reason' => 'permission',
        ];
    }
}
