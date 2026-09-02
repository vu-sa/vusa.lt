<?php

namespace App\Support;

use App\Actions\AnnounceMeetingInCalendar;
use App\Enums\MeetingType;
use App\Models\Meeting;
use Illuminate\Support\Carbon;

/**
 * The generated display name of a sitting, per locale.
 *
 * `meetings.title` is not authored — it is rebuilt from `start_time` on every save — so it is
 * stored as plain Lithuanian and localized here at read time instead of becoming a translatable
 * column. Same division of labour as {@see AnnounceMeetingInCalendar::title()}.
 */
class MeetingTitle
{
    public static function for(Meeting $meeting, string $locale): string
    {
        return self::build($meeting->start_time, $meeting->type, $locale);
    }

    /**
     * Email meetings carry a 23:59 deadline marker in `start_time`, so the time is dropped
     * rather than shown as a misleading "23.59 val.".
     */
    public static function build(mixed $startTime, mixed $type, string $locale): string
    {
        $typeValue = $type instanceof MeetingType ? $type->value : $type;
        $isEmail = $typeValue === MeetingType::Email->value;

        if ($locale === 'en') {
            $format = $isEmail ? 'D MMMM YYYY' : 'D MMMM YYYY HH.mm';

            return Carbon::parse($startTime)->locale('en')->isoFormat($format).' meeting';
        }

        $format = $isEmail ? 'YYYY MMMM DD [d.]' : 'YYYY MMMM DD [d.] HH.mm [val.]';

        return Carbon::parse($startTime)->locale('lt-LT')->isoFormat($format).' posėdis';
    }
}
