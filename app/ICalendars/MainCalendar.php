<?php

namespace App\ICalendars;

use App\Models\Calendar as CalendarModel;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Illuminate\Support\Carbon;
use DateTime;

class MainCalendar {

    private function parseCalendarEventsForICS($calendars, $en = false) {
        // foreach event in calendar
        // create event in ICS
        $events = [];

        foreach ($calendars as $event) {
            $eventObject = Event::create($event->title)->startsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->date));

            $eventObject->description($en 
                ? (strip_tags(($event?->attributes['en']['description'] ?? null) ?? $event->description))
                : strip_tags($event->description));

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

        dd($events);

        return $events;
    }
    
    public function get()  {
        
        // get lang from request
        $lang = request()->lang;

        if ($lang === 'en') {
            $calendars = CalendarModel::where('attributes->en->shown', 'true')->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'description', 'attributes')->take(250)->get();
        } else {
            $calendars = CalendarModel::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'description')->take(250)->get();
        }

        // get last calendar models
        $calendarArray = $this->parseCalendarEventsForICS($calendars, $lang === 'en');

        $calendar = Calendar::create('Studentiškas kalendorius (VU SA)')->description('Studentiškų veiklų kalendorius Vilniaus universitete. Kuruojamas VU Studentų atstovybės 🔬')->refreshInterval(5)
        ->event($calendarArray)
        ->get();

        return $calendar;
        }
}