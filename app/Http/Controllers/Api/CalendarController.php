<?php

namespace App\Http\Controllers\Api;

use App\Models\Calendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CalendarController extends ApiController
{
    /**
     * Get calendar events for a tenant (public endpoint).
     */
    public function getTenantCalendar(): JsonResponse
    {
        $calendar = $this->getEventsForCalendar();

        return $this->jsonSuccess($calendar);
    }

    protected function getEventsForCalendar()
    {
        if (app()->getLocale() === 'en') {
            return Cache::remember('calendar_en', 60 * 30, function () {
                return Calendar::query()
                    ->with(['category', 'media'])
                    ->where('is_international', true)
                    ->where('is_draft', false)
                    ->orderBy('date', 'desc')
                    ->take(100)
                    ->get()
                    ->map(function ($event) {
                        $event->images = $event->getMedia('images');

                        return $event;
                    });
            });
        } else {
            return Cache::remember('calendar_lt', 60 * 30, function () {
                return Calendar::query()
                    ->with(['category', 'media', 'tenant:id,shortname'])
                    ->where('is_draft', false)
                    ->orderBy('date', 'desc')
                    ->take(100)
                    ->get()
                    ->map(function ($event) {
                        $event->images = $event->getMedia('images');

                        return $event;
                    });
            });
        }
    }
}
