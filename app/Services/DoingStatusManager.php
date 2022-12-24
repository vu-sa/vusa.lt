<?php

namespace App\Services;

use App\Models\Doing;
use Illuminate\Support\Carbon;

// This is, now, for calculating what kind of status is assigned for a doing.
// Most of the doing statuses must be applied automatically

class DoingStatusManager {
    
    public static function generateStatusForNewDoing(Doing $doing) {
        $weekdaysBeforeDoing = self::getRelativeWeekdaysBeforeDoing($doing);

        switch (true) {
            case $weekdaysBeforeDoing < 0:
                $doing->status = "Sukurtas po įvykio";
                break;
            case $weekdaysBeforeDoing < 3:
                $doing->status = "Sukurtas per vėlai";
                break;
            default: 
                $doing->status = "Sukurtas";
                break;
        }

        $doing->save();
    }

    protected static function getRelativeWeekdaysBeforeDoing($doing): int {
        $currentDate = self::roundDateToMidnight(now());
        
        // check if date is 3 workdays before
        $objectDay = Carbon::parse($doing->date)->startOfDay();
        
        $days = $currentDate->diffInWeekdays($objectDay, false);

        return $days;
    }

    protected static function roundDateToMidnight(Carbon $date): Carbon {
        if ($date->hour < 10) {
            $roundedDate = $date->startOfDay();
        } else {
            $roundedDate = $date->startOfDay()->addDays(1);
        }

        return $roundedDate;
    }
}