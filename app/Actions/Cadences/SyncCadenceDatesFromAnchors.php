<?php

namespace App\Actions\Cadences;

use App\Models\Cadence;
use App\Models\Meeting;

/**
 * Pulls an anchored term's boundaries back onto the meetings that set them.
 *
 * `start_date` / `end_date` stay the stored, authoritative values — everything that reads a
 * cadence reads those, and nothing else has to learn about anchors. The anchor is a source,
 * not a second answer, which is the same arrangement {@see Meeting::syncCalendarEventTiming}
 * uses for the announcement it owns.
 */
class SyncCadenceDatesFromAnchors
{
    /**
     * @return bool whether either boundary moved
     */
    public static function execute(Cadence $cadence): bool
    {
        $start = self::dateOf($cadence->startMeeting);
        $end = self::dateOf($cadence->endMeeting);

        if ($start !== null) {
            $cadence->start_date = $start;
        }

        if ($end !== null) {
            $cadence->end_date = $end;
        }

        return $cadence->isDirty(['start_date', 'end_date']);
    }

    /**
     * Every cadence anchored to this meeting, brought back in line with its new date.
     *
     * Saved through the model rather than a query-builder update so a cadence's own events
     * still fire — the same reason the calendar announcement is saved that way.
     */
    public static function forMeeting(Meeting $meeting): void
    {
        Cadence::query()
            ->where('start_meeting_id', $meeting->id)
            ->orWhere('end_meeting_id', $meeting->id)
            ->with(['startMeeting:id,start_time', 'endMeeting:id,start_time'])
            ->get()
            ->each(function (Cadence $cadence): void {
                if (self::execute($cadence)) {
                    $cadence->save();
                }
            });
    }

    private static function dateOf(?Meeting $meeting): ?string
    {
        return $meeting?->start_time?->toDateString();
    }
}
