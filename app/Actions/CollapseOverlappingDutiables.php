<?php

namespace App\Actions;

use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared by duty and user merges to widen overlapping terms and fill blank details.
 * Separate stints and different tenant scopes stay apart.
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
     * Shared by automatic overlap collapse and the timeline's explicit merge.
     * Repoints linked seats before the losing source is deleted, preserving their provenance.
     */
    public static function foldInto(Dutiable $survivor, Dutiable $loser): void
    {
        ReconcileExOfficioSeats::paused(function () use ($survivor, $loser): void {
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

            self::copyDetails($survivor, $loser);

            foreach ($loser->derivedDutiables()->get() as $derived) {
                $oldSourceId = $derived->via_dutiable_id;
                $keptSeat = $derived->ended_by_institution_deactivation ? null : $survivor->derivedDutiables()
                    ->where('duty_id', $derived->duty_id)->where('ended_by_institution_deactivation', false)
                    ->orderBy('id')->first();
                if ($keptSeat !== null) {
                    self::copyDetails($keptSeat, $derived);
                    activity()->performedOn($keptSeat)->event('source_repointed')
                        ->withProperties(['old' => ['via_dutiable_id' => $oldSourceId], 'via_dutiable_id' => $survivor->id,
                            'removed_seat_id' => $derived->id])
                        ->log('ex_officio_source_repointed');
                    $derived->delete();

                    continue;
                }
                $derived->update(['via_dutiable_id' => $survivor->id]);
                activity()->performedOn($derived)->event('source_repointed')
                    ->withProperties(['old' => ['via_dutiable_id' => $oldSourceId], 'via_dutiable_id' => $survivor->id])
                    ->log('ex_officio_source_repointed');
            }
        });
        ReconcileExOfficioSeats::forSource($survivor);
    }

    public static function copyDetails(Dutiable $survivor, Dutiable $loser): void
    {
        foreach (['study_program_id', 'study_program_note', 'additional_email', 'description'] as $field) {
            if (in_array($field, $loser->getTranslatableAttributes(), true)) {
                foreach ($loser->getTranslations($field) as $locale => $value) {
                    if (blank($survivor->getTranslation($field, $locale, false))) {
                        $survivor->setTranslation($field, $locale, $value);
                    }
                }
            } elseif (blank($survivor->{$field}) && filled($loser->{$field})) {
                $survivor->{$field} = $loser->{$field};
            }
        }

        if (blank($survivor->additional_photo) && filled($loser->additional_photo)) {
            $survivor->additional_photo = $loser->additional_photo;
            $survivor->additional_photo_focal_point = $loser->additional_photo_focal_point;
        }

        $survivor->save();
        self::mergePhotoMedia($survivor, $loser);
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
