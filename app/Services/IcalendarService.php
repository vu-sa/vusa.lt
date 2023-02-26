<?php

namespace App\Services;

use App\Models\Calendar as CalendarModel;
use DateTime;
use Illuminate\Support\Carbon;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class IcalendarService
{
    private function parseCalendarEventsForICS($calendars, $en = false)
    {
        // foreach event in calendar
        // create event in ICS
        $events = [];

        foreach ($calendars as $event) {
            $eventObject = Event::create($en ? ($event?->extra_attributes['en']['title'] ?? $event->title) : $event->title)->startsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->date));

            $eventObject->description($en
                ? (strip_tags(($event?->extra_attributes['en']['description'] ?? $event->description) ?? $event->description))
                : strip_tags($event->description));

            // there are many old events that have no end date. we need to manage this
            if (! is_null($event->end_date)) {
                // if has end date, assign end date
                $eventObject->endsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->end_date));
            } else {
                // check if event date hour is midnight
                if (Carbon::parse($event->date)->hour.Carbon::parse($event->date)->minute == '0000') {
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

            if (($event->extra_attributes['all_day'] ?? null) === true) {
                $eventObject->fullDay();
            }

            if (! is_null($event->url)) {
                $eventObject->url($event->url);
            }

            $events[] = $eventObject;
        }

        return $events;
    }

    public function get()
    {
        // get lang from request
        $lang = request()->lang;

        if ($lang === 'en') {
            $calendars = CalendarModel::where('extra_attributes->en->shown', 'true')->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'description', 'extra_attributes')->take(250)->get();
        } else {
            $calendars = CalendarModel::orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'description', 'extra_attributes')->take(250)->get();
        }

        // get last calendar models
        $calendarArray = $this->parseCalendarEventsForICS($calendars, $lang === 'en');

        $calendar = Calendar::create($lang === 'en' ? 'Student activity calendar (VU SA)' : 'Studentiškas kalendorius (VU SA)')->description($lang === 'en' ? 'Calendar of student activities at Vilnius University. Curated by VU Students\' Representation 🔬' : 'Studentiškų veiklų kalendorius Vilniaus universitete. Kuruojamas VU Studentų atstovybės 🔬')->refreshInterval(5)
        ->event($calendarArray)
        ->get();

        return $calendar;
    }
}
