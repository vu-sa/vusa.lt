<?php

namespace App\Services;

use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MeetingRepresentativeResolver
{
    /** @return Collection<int, User> */
    public function resolve(Meeting $meeting, bool $withDuties = true): Collection
    {
        $meetingDate = $meeting->start_time->toDateString();

        $institutionIds = $meeting->institutions->pluck('id');

        if ($institutionIds->isEmpty()) {
            return new Collection;
        }

        $dutyIds = Duty::query()
            ->whereIn('institution_id', $institutionIds)
            ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
            ->pluck('id');

        if ($dutyIds->isEmpty()) {
            return new Collection;
        }

        return User::query()
            ->whereHas('duties', function ($query) use ($dutyIds, $meetingDate): void {
                $query->whereIn('duties.id', $dutyIds)
                    ->whereDate('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate): void {
                        $q->whereNull('dutiables.end_date')
                            ->orWhereDate('dutiables.end_date', '>=', $meetingDate);
                    });
            })
            ->when($withDuties, fn ($query) => $query->with(['duties' => function ($query) use ($dutyIds, $meetingDate): void {
                $query->whereIn('duties.id', $dutyIds)
                    ->whereDate('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate): void {
                        $q->whereNull('dutiables.end_date')
                            ->orWhereDate('dutiables.end_date', '>=', $meetingDate);
                    });
            }]))
            ->get()
            ->unique('id');
    }
}
