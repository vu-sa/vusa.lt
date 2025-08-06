<?php

namespace App\Services;

use App\Models\Calendar as CalendarModel;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class IcalendarService
{
    /**
     * @param  Collection<CalendarModel>  $calendars  Calendar events to parse
     * @param  bool  $en  Use English translations
     * @return array<Event> Array of ICS Event objects
     */
    private function parseCalendarEventsForICS(Collection $calendars, bool $en = false): array
    {
        // foreach event in calendar
        // create event in ICS
        $events = [];

        foreach ($calendars as $event) {
            $eventObject = Event::create($en ? $event->getTranslation('title', 'en') : $event->getTranslation('title', 'lt'))->uniqueIdentifier((string) $event->id)->startsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->date));

            $eventObject->description($en
                ? strip_tags($event->getTranslation('description', 'en')) : strip_tags($event->getTranslation('description', 'lt')));

            // There are many old events that have no end date. we need to manage this
            if (! is_null($event->end_date)) {
                // if has end date, assign end date
                $eventObject->endsAt(DateTime::createFromFormat('Y-m-d H:i:s', $event->end_date));
            } else {
                // check if event date hour is midnight
                if (sprintf('%02d%02d', Carbon::parse($event->date)->hour, Carbon::parse($event->date)->minute) == '0000') {
                    // make event full day
                    $eventObject->fullDay();
                } else {
                    // create end date as +1 hour to event start date
                    $eventObject->endsAt(Carbon::parse($event->date)->addHour()->toDateTime());
                }
            }

            if ($event->location) {
                $eventObject->address($en ? $event->getTranslation('location', 'en') : $event->getTranslation('location', 'lt'));
            }

            if ($event->is_all_day) {
                $eventObject->fullDay();
            }

            if ($event->cto_url) {
                $eventObject->url($event->getTranslation('cto_url', $en ? 'en' : 'lt'));
            }

            $events[] = $eventObject;
        }

        return $events;
    }

    public function get(): string
    {
        // get lang from request
        $lang = request()->lang;

        if ($lang === 'en') {
            $calendars = CalendarModel::query()->where('is_international', true)->where('is_draft', false)->orderBy('date', 'desc')->select('id', 'date', 'end_date', 'title', 'description', 'facebook_url')->take(250)->get();
        } else {
            $calendars = CalendarModel::query()->orderBy('date', 'desc')->where('is_draft', false)->select('id', 'date', 'end_date', 'title', 'description', 'facebook_url')->take(250)->get();
        }

        // get last calendar models
        $calendarArray = $this->parseCalendarEventsForICS($calendars, $lang === 'en');

        $calendar = Calendar::create($lang === 'en' ? 'Student activity calendar (VU SA)' : 'Studentiškas kalendorius (VU SA)')->description($lang === 'en' ? 'Calendar of student activities at Vilnius University. Curated by VU Students\' Representation 🔬' : 'Studentiškų veiklų kalendorius Vilniaus universitete. Kuruojamas VU Studentų atstovybės 🔬')->refreshInterval(5)
            ->event($calendarArray)
            ->get();

        return $calendar;
    }
}
