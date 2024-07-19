<?php

namespace App\Observers;

use App\Models\Calendar;
use Illuminate\Support\Facades\Cache;

class CalendarObserver
{
    /**
     * Handle the Calendar "created" event.
     */
    public function saved(Calendar $calendar): void
    {
        Cache::forget('calendar_lt');
        Cache::forget('calendar_en');
    }
}
