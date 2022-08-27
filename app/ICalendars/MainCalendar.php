<?php

namespace App\ICalendars;

use App\Models\Calendar as CalendarModel;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Illuminate\Support\Carbon;
use DateTime;

class MainCalendar {

    private function parseCalendarEventsForICS($calendars) {
        // foreach event in calendar
        // create event in ICS
        $events = [];

        foreach ($calendars as $event) {
            $eventObject = Event::create($event->title)->startsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->date))->description(strip_tags($event->description) ?? '');       

            // there are many old events that have no end date. we need to manage this
            if (! is_null($event->end_date)) {
                // if has end date, assign end date
                $eventObject->endsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->end_date));

            } else {
                // check if event date hour is midnight
                if (Carbon::parse($event->date)->hour . Carbon::parse($event->date)->minute == '0000') {
                    // make event full day
                    $eventObject->fullDay();
                } else {
                    // create end date as +1 hour to event start date
                    $eventObject->endsAt(Carbon::parse($event->date)->addHour()->toDateTime());
                    
                }
            }

            if (! is_null($event->location)) {
                $eventObject->address($event->location);
            }

            if (! is_null($event->url)) {
                $eventObject->url($event->url);
            }

            $events[] = $eventObject;
        }

        return $events;
    }
    
    public function get()  {
        
        // get last 100 calendar models
        $calendars = CalendarModel::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'description')->take(250)->get();

        $calendarArray = $this->parseCalendarEventsForICS($calendars);

        $calendar = Calendar::create('Studentiškas kalendorius (VU SA)')->description('Studentiškų veiklų kalendorius Vilniaus universitete. Kuruojamas VU Studentų atstovybės 🔬')->refreshInterval(5)
        ->event($calendarArray)
        ->get();

        return $calendar;
        }
}