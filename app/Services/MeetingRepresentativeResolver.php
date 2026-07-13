<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\Meeting;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MeetingRepresentativeResolver
{
    /**
     * Get student representatives who were active at the time of a meeting.
     * Filters duties by 'studentu-atstovai' type and checks dutiables pivot dates.
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

        // Get the student representative type
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first();

        if (! $studentRepType) {
            return new Collection;
        }

        // Get duties that belong to these institutions and have the student rep type
        $dutyIds = Duty::query()
            ->whereIn('institution_id', $institutionIds)
            ->whereHas('types', fn ($q) => $q->where('types.id', $studentRepType->id))
            ->pluck('id');

        if ($dutyIds->isEmpty()) {
            return new Collection;
        }

        // Get users who were active in these duties at the meeting date
        return User::query()
            ->whereHas('duties', function ($query) use ($dutyIds, $meetingDate) {
                $query->whereIn('duties.id', $dutyIds)
                    ->where('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate) {
                        $q->whereNull('dutiables.end_date')
                            ->orWhere('dutiables.end_date', '>=', $meetingDate);
                    });
            })
            ->with(['duties' => function ($query) use ($dutyIds, $meetingDate) {
                $query->whereIn('duties.id', $dutyIds)
                    ->where('dutiables.start_date', '<=', $meetingDate)
                    ->where(function ($q) use ($meetingDate) {
                        $q->whereNull('dutiables.end_date')
                            ->orWhere('dutiables.end_date', '>=', $meetingDate);
                    });
            }])
            ->get()
            ->unique('id');
    }
}
