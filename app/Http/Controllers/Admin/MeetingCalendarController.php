<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AnnounceMeetingInCalendar;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Meetings\DestroyMeetingCalendarEventRequest;
use App\Http\Requests\Meetings\StoreMeetingCalendarEventRequest;
use App\Models\Calendar;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;

/**
 * Links a meeting to the calendar event that announces it.
 *
 * Publishing that event shows the agenda inline on the event page (see
 * PublicPageController::meetingBehind()) regardless of settings — but does not by itself open
 * the meeting page or search entry, which stay gated on Meeting::isPubliclyVisible().
 */
class MeetingCalendarController extends AdminController
{
    public function store(StoreMeetingCalendarEventRequest $request, Meeting $meeting): RedirectResponse
    {
        $calendarId = $request->validated('calendar_id');

        if ($calendarId === null) {
            AnnounceMeetingInCalendar::execute($meeting);

            return back()->with('success', __('messages.meeting.calendar_event_created'));
        }

        // The id is already constrained to unlinked events in an authorized tenant by the request.
        $event = Calendar::query()->findOrFail($calendarId);
        $event->meeting_id = $meeting->id;
        $event->save();

        return back()->with('success', __('messages.meeting.calendar_event_linked'));
    }

    public function destroy(DestroyMeetingCalendarEventRequest $request, Meeting $meeting): RedirectResponse
    {
        $event = $meeting->calendarEvent;

        if ($event !== null) {
            $event->meeting_id = null;
            $event->save();
        }

        return back()->with('success', __('messages.meeting.calendar_event_unlinked'));
    }
}
