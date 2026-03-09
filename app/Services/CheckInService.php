<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use Illuminate\Support\Carbon;

class CheckInService
{
    /**
     * Create a new check-in for a period where no meeting is expected
     */
    public function create(User $user, Institution $institution, Carbon $startDate, Carbon $endDate, ?string $note = null): InstitutionCheckIn
    {
        $checkIn = new InstitutionCheckIn([
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'tenant_id' => $institution->tenant_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'note' => $note,
        ]);

        $checkIn->save();

        return $checkIn;
    }

    /**
     * Delete all active check-ins for an institution
     */
    public function deleteActive(Institution $institution): int
    {
        $count = 0;
        foreach ($institution->activeCheckIns()->get() as $checkIn) {
            $checkIn->delete();
            $count++;
        }

        return $count;
    }

    /**
     * Adjust check-ins when a meeting is created.
     * If a meeting date falls within an active check-in period,
     * reduce the check-in end_date to the day before the meeting.
     * If the meeting is on or before the start_date, delete the check-in.
     */
    public function adjustForMeeting(Institution $institution, Carbon $meetingDate): void
    {
        $checkIns = $institution->checkIns()
            ->where('start_date', '<=', $meetingDate)
            ->where('end_date', '>=', $meetingDate)
            ->get();

        foreach ($checkIns as $checkIn) {
            if ($meetingDate->lte($checkIn->start_date)) {
                // Meeting is on or before start date - delete the check-in
                $checkIn->delete();
            } else {
                // Reduce end_date to day before meeting
                $checkIn->end_date = $meetingDate->copy()->subDay();
                $checkIn->save();
            }
        }
    }
}
