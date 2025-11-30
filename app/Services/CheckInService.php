<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
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
     * Delete a check-in
     */
    public function delete(InstitutionCheckIn $checkIn): void
    {
        $checkIn->delete();
    }
}
