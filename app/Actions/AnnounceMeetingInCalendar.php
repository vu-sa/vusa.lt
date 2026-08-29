<?php

namespace App\Actions;

use App\Enums\CalendarHeroStyleEnum;
use App\Enums\MeetingType;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;

/**
 * Turn a meeting into a calendar announcement.
 *
 * Created as a draft: publishing is what opens the agenda to the public on the event page (see
 * PublicPageController::meetingBehind()), so it stays a deliberate second step by the editor.
 */
class AnnounceMeetingInCalendar
{
    public static function execute(Meeting $meeting): Calendar
    {
        $meeting->loadMissing('institutions');

        /** @var Institution|null $institution */
        $institution = $meeting->institutions->first();

        $event = new Calendar;
        $event->setTranslation('title', 'lt', self::title($institution, 'lt'));
        $event->setTranslation('title', 'en', self::title($institution, 'en'));
        $event->date = $meeting->start_time;
        $event->end_date = $meeting->end_time;
        $event->tenant_id = $institution?->tenant_id;
        $event->meeting_id = $meeting->id;
        $event->is_draft = true;
        $event->is_all_day = false;
        $event->is_remote = $meeting->type === MeetingType::Remote;
        // A posėdis has no hero photo; the loud variants only look empty.
        $event->hero_style = CalendarHeroStyleEnum::MINIMAL;
        $event->save();

        return $event;
    }

    /**
     * Matches how these events have been named by hand for a decade: "VU SA Parlamento posėdis".
     */
    private static function title(?Institution $institution, string $locale): string
    {
        $name = $institution?->getTranslation('short_name', $locale)
            ?: $institution?->getTranslation('name', $locale)
            ?: '';

        $suffix = $locale === 'en' ? 'meeting' : 'posėdis';

        return trim($name.' '.$suffix);
    }
}
