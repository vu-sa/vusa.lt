<?php

namespace App\Actions;

use App\Enums\CalendarHeroStyleEnum;
use App\Enums\MeetingType;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Support\MeetingTitle;

/** Publishing the draft opens the agenda on the event page. */
class AnnounceMeetingInCalendar
{
    public static function execute(Meeting $meeting): Calendar
    {
        $meeting->loadMissing('institutions');

        /** @var Institution|null $institution */
        $institution = $meeting->institutions->first();

        $event = new Calendar;
        $event->setTranslation('title', 'lt', self::title($meeting, $institution, 'lt'));
        $event->setTranslation('title', 'en', self::title($meeting, $institution, 'en'));
        $event->date = $meeting->start_time;
        $event->end_date = $meeting->end_time;
        $event->tenant_id = $institution?->tenant_id;
        $event->meeting_id = $meeting->id;
        $event->is_draft = true;
        $event->is_all_day = false;
        $event->is_remote = $meeting->type === MeetingType::Remote;
        // A meeting has no hero photo; the loud variants only look empty.
        $event->hero_style = CalendarHeroStyleEnum::MINIMAL;
        $event->save();

        return $event;
    }

    /**
     * Matches how these events have been named by hand for a decade: "VU SA Parlamento posėdis".
     */
    private static function title(Meeting $meeting, ?Institution $institution, string $locale): string
    {
        $name = $institution?->getTranslation('short_name', $locale)
            ?: $institution?->getTranslation('name', $locale)
            ?: '';

        $suffix = MeetingTitle::noun($meeting->type, $locale, $meeting->meetingKind());

        return trim($name.' '.$suffix);
    }
}
