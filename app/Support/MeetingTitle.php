<?php

namespace App\Support;

use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Models\Meeting;
use Illuminate\Support\Carbon;

/**
 * The one builder of a meeting's display name, per locale.
 *
 * `meetings.title` is not authored: Meeting::refreshTitle() stores the Lithuanian name, rebuilt
 * when the date, type or the institutions' meeting kind changes. Other locales render here.
 */
class MeetingTitle
{
    public static function for(Meeting $meeting, string $locale): string
    {
        return self::build($meeting->start_time, $meeting->type, $locale, $meeting->meetingKind());
    }

    /**
     * Email meetings carry a 23:59 deadline marker in `start_time`, so the time is dropped
     * rather than shown as a misleading "23.59 val.", and they read as a decision, not a sitting.
     */
    public static function build(mixed $startTime, mixed $type, string $locale, MeetingKind $kind = MeetingKind::Sitting): string
    {
        $isEmail = self::isEmail($type);
        $noun = self::noun($type, $locale, $kind);

        if ($locale === 'en') {
            $format = $isEmail ? 'D MMMM YYYY' : 'D MMMM YYYY HH.mm';

            return Carbon::parse($startTime)->locale('en')->isoFormat($format).' '.$noun;
        }

        $format = $isEmail ? 'YYYY MMMM DD [d.]' : 'YYYY MMMM DD [d.] HH.mm [val.]';

        return Carbon::parse($startTime)->locale('lt-LT')->isoFormat($format).' '.$noun;
    }

    /** The words after the date, for headings that show the date their own way. */
    public static function noun(mixed $type, string $locale, MeetingKind $kind = MeetingKind::Sitting): string
    {
        if (self::isEmail($type)) {
            return $locale === 'en' ? 'decision by email' : 'sprendimas el. paštu';
        }

        return $kind->noun($locale);
    }

    private static function isEmail(mixed $type): bool
    {
        return ($type instanceof MeetingType ? $type->value : $type) === MeetingType::Email->value;
    }
}
