<?php

namespace App\Http\Controllers\Api;

use App\Models\Calendar;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends ApiController
{
    /**
     * Default days in the past for the event timeline component.
     */
    private const TIMELINE_DEFAULT_DAYS_PAST = 7;

    /**
     * Default days in the future for the event timeline component.
     */
    private const TIMELINE_DEFAULT_DAYS_FUTURE = 21;

    /**
     * Maximum range in days allowed for a single request.
     * Set to 455 days (~15 months) to allow fetching a full year of past events
     * plus a few months future in a single request. This covers seasonal events
     * like freshmen camps (August) when viewing in winter months.
     */
    private const MAX_RANGE_DAYS = 455;

    /**
     * Get calendar events for a tenant (public endpoint).
     *
     * Designed for the EventCalendarElement/EventTimeline component with date-based fetching.
     *
     * @route GET /api/v1/tenants/{tenant}/calendar
     *
     * @routeName api.v1.tenants.calendar.index
     *
     * @queryParam lang string The language (lt|en). Defaults to app locale.
     * @queryParam all_tenants bool Whether to fetch from all tenants. Defaults to false.
     * @queryParam date_from string ISO date (Y-m-d) for range start. Defaults to today - 7 days.
     * @queryParam date_to string ISO date (Y-m-d) for range end. Defaults to today + 21 days.
     */
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $lang = $request->query('lang', app()->getLocale());
        $allTenants = $request->boolean('all_tenants', false);

        // Parse date range with defaults matching timeline component
        $today = Carbon::today();
        $dateFrom = $request->query('date_from')
            ? Carbon::parse($request->query('date_from'))->startOfDay()
            : $today->copy()->subDays(self::TIMELINE_DEFAULT_DAYS_PAST);

        $dateTo = $request->query('date_to')
            ? Carbon::parse($request->query('date_to'))->endOfDay()
            : $today->copy()->addDays(self::TIMELINE_DEFAULT_DAYS_FUTURE)->endOfDay();

        // Enforce maximum range to prevent overly large queries
        if ($dateFrom->diffInDays($dateTo) > self::MAX_RANGE_DAYS) {
            $dateTo = $dateFrom->copy()->addDays(self::MAX_RANGE_DAYS)->endOfDay();
        }

        // Ensure date_from is before date_to
        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->startOfDay(), $dateFrom->endOfDay()];
        }

        $query = Calendar::query()
            ->with(['category', 'media', 'tenant:id,shortname'])
            ->where('is_draft', false)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderByDesc('date');

        if ($lang === 'en') {
            $query->where('is_international', true);
        } elseif (! $allTenants) {
            $query->where('tenant_id', $tenant->id);
        }

        $events = $query->get()->map(function ($event) {
            $event->images = $event->getMedia('images');

            return $event;
        });

        return $this->jsonSuccess($events, null, [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'max_range_days' => self::MAX_RANGE_DAYS,
        ]);
    }
}
