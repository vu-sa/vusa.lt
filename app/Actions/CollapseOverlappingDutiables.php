<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Folds one holder's overlapping rows on a duty into one, keeping the widest dates and backfilling
 * blank fields. Separate stints and different tenant scopes stay apart. Used by duty and user merges.
 */
class CollapseOverlappingDutiables
{
    /**
     * Collapse every set of overlapping rows on the given duty.
     *
     * @return int The number of redundant rows removed.
     */
    public static function execute(Duty $kept): int
    {
        $collapsed = 0;

        $rows = Dutiable::query()
            ->where('duty_id', $kept->id)
            ->orderBy('dutiable_type')
            ->orderBy('dutiable_id')
            ->orderBy('tenant_id')
            ->orderBy('start_date')->orderBy('id')
            ->get();

        foreach ($rows->groupBy(fn (Dutiable $row) => $row->dutiable_type.'|'.$row->dutiable_id.'|'.$row->tenant_id) as $group) {
            foreach (self::overlappingGroups($group) as $overlap) {
                $survivor = $overlap->first();
                foreach ($overlap->skip(1) as $row) {
                    self::foldInto($survivor, $row);
                    $row->delete();
                    $collapsed++;
                }
            }
        }

        return $collapsed;
    }

    /**
     * Ex-officio rows stay alone: they mirror their source row, which the collapse folds instead.
     *
     * @param  Collection<int, Dutiable>  $rows
     * @return Collection<int, Collection<int, Dutiable>>
     */
    public static function overlappingGroups(Collection $rows): Collection
    {
        [$derived, $manual] = $rows->partition(fn (Dutiable $row) => $row->via_dutiable_id !== null);
        $groups = $derived->map(fn (Dutiable $row) => collect([$row]))->values();
        $running = null;
        $group = collect();

        foreach ($manual->sortBy('start_date') as $row) {
            if ($running === null || ! self::overlapsRunningInterval($running, $row)) {
                $group = collect();
                $groups->push($group);
                $running = clone $row;
            } elseif ($running->end_date !== null && ($row->end_date === null || $row->end_date->gt($running->end_date))) {
                $running->end_date = $row->end_date;
            }

            $group->push($row);
        }

        return $groups;
    }

    /**
     * $rows arrive start_date-sorted, so $next's start is never before
     * $survivor's — it overlaps unless $survivor already ended before it began.
     */
    private static function overlapsRunningInterval(Dutiable $survivor, Dutiable $next): bool
    {
        if ($survivor->ended_by_institution_deactivation || $next->ended_by_institution_deactivation) {
            return false;
        }

        return $survivor->end_date === null || $next->start_date->lte($survivor->end_date);
    }

    /**
     * Widen `$survivor` to also cover `$loser`, then backfill anything it is missing.
     *
     * Public because the timeline editor's explicit merge folds rows the same way — the
     * only difference there is that the user chose them rather than an overlap scan.
     */
    public static function foldInto(Dutiable $survivor, Dutiable $loser): void
    {
        if ($survivor->ended_by_institution_deactivation || $loser->ended_by_institution_deactivation) {
            throw ValidationException::withMessages([
                'rows' => __('forms.helpers.institution_assignment_closed'),
            ]);
        }

        if ($survivor->end_date !== null) {
            $survivor->end_date = $loser->end_date === null || $loser->end_date->gt($survivor->end_date)
                ? $loser->end_date
                : $survivor->end_date;
        }

        foreach (['study_program_id', 'study_program_note', 'additional_email', 'description'] as $field) {
            if (blank($survivor->{$field}) && filled($loser->{$field})) {
                $survivor->{$field} = $loser->{$field};
            }
        }

        if (blank($survivor->additional_photo) && filled($loser->additional_photo)) {
            $survivor->additional_photo = $loser->additional_photo;
            $survivor->additional_photo_focal_point = $loser->additional_photo_focal_point;
        }

        $survivor->save();
        self::mergePhotoMedia($survivor, $loser);

        foreach ($loser->derivedDutiables()->get() as $derived) {
            $derived->via_dutiable_id = $survivor->id;
            if (! $derived->ended_by_institution_deactivation) {
                $derived->start_date = $survivor->start_date;
                $derived->end_date = $survivor->end_date;
            }
            $derived->save();
        }
        foreach ($survivor->derivedDutiables()->get() as $derived) {
            if (! $derived->ended_by_institution_deactivation) {
                $derived->update(['start_date' => $survivor->start_date, 'end_date' => $survivor->end_date]);
            }
        }
        $seats = $survivor->derivedDutiables()->orderBy('start_date')->orderBy('id')->get();
        foreach ($seats->groupBy(fn (Dutiable $seat) => $seat->duty_id.'|'.$seat->tenant_id) as $group) {
            $kept = $group->first();
            foreach ($group->skip(1) as $seat) {
                $fields = ['study_program_id', 'study_program_note', 'additional_email', 'description',
                    'additional_photo', 'additional_photo_focal_point', 'use_original_duty_name',
                    'ended_by_institution_deactivation'];
                $sameDetails = collect($fields)->every(fn (string $field) => $kept->getAttribute($field) === $seat->getAttribute($field));

                if ($sameDetails && $seat->getMedia('photo')->isEmpty()) {
                    $seat->delete();
                }
            }
        }

    }

    private static function mergePhotoMedia(Dutiable $survivor, Dutiable $loser): void
    {
        // The loser is deleted next, and its media with it.
        $loserPhoto = $loser->getFirstMedia('photo');

        if ($loserPhoto !== null && $survivor->getFirstMedia('photo') === null) {
            $loserPhoto->model()->associate($survivor);
            $loserPhoto->save();
            $loser->unsetRelation('media');
            $survivor->unsetRelation('media');
        }

        if ($survivor->getFirstMedia('photo') !== null) {
            $survivor->refreshImageCache('photo');
            DB::afterCommit(fn () => $survivor->fresh()?->afterImageMediaChanged('photo'));
        }
    }
}
