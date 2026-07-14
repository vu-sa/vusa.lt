<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\AcademicCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AcademicCalendarApiController extends ApiController
{
    /**
     * Bump when the vacation rules in AcademicCalendarService change, so cached
     * responses from the previous rules are not served.
     */
    private const CACHE_VERSION = 'v1';

    private const CACHE_TTL_DAYS = 30;

    /**
     * Largest span of years a single request may ask for.
     */
    private const MAX_YEAR_SPAN = 20;

    /**
     * Academic vacation periods, used by the meetings Gantt chart to shade breaks.
     *
     * The same periods are excluded from institution inactivity counting, so the
     * chart and the periodicity tasks stay in agreement.
     */
    public function vacations(Request $request, AcademicCalendarService $calendar): JsonResponse
    {
        $this->requireAuth($request);

        $validated = $request->validate([
            'from_year' => 'nullable|integer|min:1990|max:2100',
            'to_year' => 'nullable|integer|min:1990|max:2100',
        ]);

        $currentYear = (int) now()->year;
        $fromYear = (int) ($validated['from_year'] ?? $currentYear - 1);
        $toYear = (int) ($validated['to_year'] ?? $currentYear + 1);
        $toYear = max($fromYear, min($toYear, $fromYear + self::MAX_YEAR_SPAN));

        $periods = Cache::remember(
            'academic-calendar:vacations:'.self::CACHE_VERSION.":{$fromYear}-{$toYear}",
            now()->addDays(self::CACHE_TTL_DAYS),
            fn () => collect(range($fromYear, $toYear))
                ->flatMap(fn (int $year) => $calendar->vacationPeriodsForYear($year))
                ->map(fn (array $period) => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                    'type' => $period['type'],
                ])
                ->values()
                ->all()
        );

        return $this->jsonSuccess($periods)
            ->header('Cache-Control', 'private, max-age=86400');
    }
}
