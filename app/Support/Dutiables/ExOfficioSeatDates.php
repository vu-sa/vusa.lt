<?php

namespace App\Support\Dutiables;

use App\Models\Pivots\Dutiable;
use Illuminate\Support\Carbon;

/** The one calculation of a seat's dates, so sync, review, preview and checks cannot disagree. */
class ExOfficioSeatDates
{
    /** @return array{dates: array{start_date: string, end_date: string|null}|null, seat: Dutiable|null, duplicates: list<Dutiable>} */
    public static function forTarget(Dutiable $source, string $targetId, iterable $seats, string $today): array
    {
        $seats = collect($seats)->where('duty_id', $targetId);
        $dates = DutiablePeriods::dates($source);
        $closureEnd = $seats->filter(fn (Dutiable $seat) => $seat->ended_by_institution_deactivation
            && $seat->end_date !== null)->map(fn (Dutiable $seat) => $seat->end_date->toDateString())->max();
        $live = $seats->where('ended_by_institution_deactivation', false);
        if ($closureEnd !== null) {
            $resume = $live->filter(fn (Dutiable $seat) => $seat->start_date->toDateString() > $closureEnd)
                ->map(fn (Dutiable $seat) => $seat->start_date->toDateString())->min();
            $dates['start_date'] = max($dates['start_date'], $resume ?? max(Carbon::parse($closureEnd)->addDay()->toDateString(), $today));
        }
        $live = $live->sortBy(fn (Dutiable $seat) => [
            -DutiablePeriods::overlap($dates, DutiablePeriods::dates($seat)),
            -(int) self::hasDetails($seat), $seat->id,
        ])->values();

        return [
            // Only a closure can leave no time to resume; a term's own dates are always mirrored.
            'dates' => $closureEnd !== null && $dates['end_date'] !== null && $dates['start_date'] > $dates['end_date'] ? null : $dates,
            'seat' => $live->first(),
            'duplicates' => $live->skip(1)->values()->all(),
        ];
    }

    private static function hasDetails(Dutiable $seat): bool
    {
        return filled($seat->additional_email) || filled($seat->additional_photo)
            || collect($seat->getTranslations('description'))->contains(fn ($value) => filled($value))
            || ($seat->relationLoaded('media') && $seat->media->isNotEmpty());
    }
}
