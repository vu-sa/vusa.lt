<?php

namespace App\Actions;

use App\Models\Calendar;

class DuplicateCalendarAction
{
    public static function execute(Calendar $calendar): Calendar
    {
        // Replicate the calendar item
        $newCalendar = $calendar->replicate();
        $newCalendar->title .= ' (kopija)';
        $newCalendar->is_draft = 1;

        $newCalendar->save();

        return $newCalendar;
    }
}
