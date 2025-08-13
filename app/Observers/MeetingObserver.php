<?php

namespace App\Observers;

use App\Models\Meeting;
use App\Services\CheckInService;

class MeetingObserver
{
    public function saved(Meeting $meeting): void
    {
        app(CheckInService::class)->invalidateByMeeting($meeting);
    }
}
