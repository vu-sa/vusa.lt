<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use Illuminate\Support\Facades\Cache;

class CalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getTenantCalendar()
    {
        $calendar = $this->getEventsForCalendar();

        return response()->json($calendar);
    }

    protected function getEventsForCalendar()
    {
        if (app()->getLocale() === 'en') {
            return Cache::remember('calendar_en', 60 * 30, function () {
                return Calendar::query()->with('category')->where('is_international', true)->where('is_draft', false)
                    ->orderBy('date', 'desc')->take(100)->get(['id', 'date', 'end_date', 'title', 'category']);
            });
        } else {
            return Cache::remember('calendar_lt', 60 * 30, function () {
                return Calendar::query()->with('category')->where('is_draft', false)->orderBy('date', 'desc')->take(100)->get();
            });
        }
    }
}
