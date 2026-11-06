<?php

namespace App\Support;

use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Models\Meeting;
use Illuminate\Support\Carbon;

/** Builds localized titles; the stored title is Lithuanian. */
class MeetingTitle
{
    public static function for(Meeting $meeting, string $locale): string
    {
        return self::build($meeting->start_time, $meeting->type, $locale, $meeting->meetingKind());
    }

    public static function build(mixed $startTime, mixed $type, string $locale, MeetingKind $kind = MeetingKind::Sitting): string
    {
        return self::date($startTime, $type, $locale).' '.self::noun($type, $locale, $kind);
    }

    /**
     * Email meetings carry a 23:59 deadline marker in `start_time`, so the time is dropped
     * rather than shown as a misleading "23.59 val.", and they read as a decision, not a sitting.
     */
    public static function date(mixed $startTime, mixed $type, string $locale): string
    {
        $isEmail = self::isEmail($type);

        if ($locale === 'en') {
            return Carbon::parse($startTime)->locale('en')->isoFormat($isEmail ? 'D MMMM YYYY' : 'D MMMM YYYY HH.mm');
        }

        return Carbon::parse($startTime)->locale('lt-LT')->isoFormat($isEmail ? 'YYYY MMMM DD [d.]' : 'YYYY MMMM DD [d.] HH.mm [val.]');
    }

    /** The words after the date, for headings that show the date their own way. */
    public static function noun(mixed $type, string $locale, MeetingKind $kind = MeetingKind::Sitting, bool $joint = false): string
    {
        if (self::isEmail($type)) {
            $noun = $locale === 'en' ? 'decision by email' : 'sprendimas el. paštu';

            return $joint ? ($locale === 'en' ? 'joint ' : 'jungtinis ').$noun : $noun;
        }

        return $joint ? $kind->jointNoun($locale) : $kind->noun($locale);
    }

    private static function isEmail(mixed $type): bool
    {
        return ($type instanceof MeetingType ? $type->value : $type) === MeetingType::Email->value;
    }
}
