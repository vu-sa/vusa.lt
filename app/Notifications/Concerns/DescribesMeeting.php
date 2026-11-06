<?php

namespace App\Notifications\Concerns;

use App\Models\Meeting;
use App\Support\MeetingTitle;
use Illuminate\Support\Arr;

trait DescribesMeeting
{
    /** "Institucijos „Senatas“ posėdis": institution names cannot be declined, so they are quoted after a declinable word. */
    protected function meetingPhrase(Meeting $meeting): string
    {
        $locale = app()->getLocale();
        $names = $meeting->institutions->pluck('name')->filter()->values();
        $noun = MeetingTitle::noun($meeting->type, $locale, $meeting->meetingKind(), joint: $names->count() > 1);

        if ($names->isEmpty()) {
            return __('notifications.meeting_phrase_without_institution', ['noun' => $noun]);
        }

        $quoted = $names->map(fn (string $name): string => __('notifications.quoted', ['text' => $name]))->all();

        return __($names->count() > 1 ? 'notifications.meeting_phrase_joint' : 'notifications.meeting_phrase', [
            'institutions' => Arr::join($quoted, ', ', ' '.__('notifications.list_and').' '),
            'noun' => $noun,
        ]);
    }

    protected function meetingDate(Meeting $meeting): string
    {
        return MeetingTitle::date($meeting->start_time, $meeting->type, app()->getLocale());
    }
}
