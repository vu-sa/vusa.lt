<?php

namespace App\Actions\Schedulable;

use App\Models\Meeting;
use App\Notifications\MeetingNotFinishedNotification;

class MeetingNotifier
{
    // Used for inform, that the meeting is not finished, i.e. the files were not uploaded
    public static function notifyOnMeetingUnfinishedStatus()
    {
        $meetings = Meeting::with('institutions.users')->whereDate('start_time', '<=', now())->where('status', '!=', 'Pabaigtas')->get();

        foreach ($meetings as $meeting) {
            $meeting->users->each(function ($user) use ($meeting) {
                $user->notify(new MeetingNotFinishedNotification($meeting));
            });
        }
    }
}
