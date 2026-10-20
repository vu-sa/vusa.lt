<?php

namespace App\Services;

use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MeetingRepresentativeResolver
{
    /**
     * Get student representatives who were active at the time of a meeting.
     * Duties holding the student representation responsibility, by dutiables pivot dates.
     *
     * @return Collection<int, User>
     */
    public function resolve(Meeting $meeting): Collection
    {
        $meetingDate = $meeting->start_time->toDateString();

        // Get all institution IDs for this meeting
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

        // Get users who were active in these duties at the meeting date
        return User::query()
            ->whereHas('duties', function ($query) use ($dutyIds, $meetingDate): void {
                $query->whereIn('duties.id', $dutyIds)
                    ->whereDate('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate): void {
                        $q->whereNull('dutiables.end_date')
                            ->orWhereDate('dutiables.end_date', '>=', $meetingDate);
                    });
            })
            ->with(['duties' => function ($query) use ($dutyIds, $meetingDate): void {
                $query->whereIn('duties.id', $dutyIds)
                    ->whereDate('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate): void {
                        $q->whereNull('dutiables.end_date')
                            ->orWhereDate('dutiables.end_date', '>=', $meetingDate);
                    });
            }])
            ->get()
            ->unique('id');
    }
}
