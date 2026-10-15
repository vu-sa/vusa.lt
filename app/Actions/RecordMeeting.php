<?php

namespace App\Actions;

use App\Enums\MeetingType;
use App\Events\MeetingFullyCreated;
use App\Models\Institution;
use App\Models\Meeting;
use App\Services\CheckInService;
use App\Support\MeetingTitle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A bare sitting of one institution — no agenda, no announcement — with the same side effects as
 * the meeting form: overlapping check-ins shrink and MeetingFullyCreated fires once it is attached.
 */
class RecordMeeting
{
    public function __construct(private readonly CheckInService $checkIns) {}

    public function execute(Institution $institution, Carbon $startTime, ?MeetingType $type = null): Meeting
    {
        $meeting = DB::transaction(function () use ($institution, $startTime, $type): Meeting {
            $meeting = Meeting::create([
                'start_time' => $startTime,
                'title' => MeetingTitle::build($startTime, $type, 'lt'),
                'type' => $type,
            ]);

            $meeting->attachAudited('institutions', $institution->id);
            $this->checkIns->adjustForMeeting($institution, $startTime);

            return $meeting;
        });

        event(new MeetingFullyCreated($meeting));

        return $meeting;
    }
}
