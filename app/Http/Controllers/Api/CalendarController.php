<?php

namespace App\Http\Controllers\Api;

use App\Models\Calendar;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CalendarController extends ApiController
{
    /**
     * Get calendar events for a tenant (public endpoint).
     *
     * @route GET /api/v1/tenants/{tenant}/calendar
     *
     * @routeName api.v1.tenants.calendar.index
     *
     * @queryParam lang string The language (lt|en). Defaults to app locale.
     * @queryParam all_tenants bool Whether to fetch from all tenants. Defaults to false.
     */
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $lang = $request->query('lang', app()->getLocale());
        $allTenants = $request->boolean('all_tenants', false);
        $cacheKey = $allTenants
            ? "calendar_{$lang}_all"
            : "calendar_{$lang}_{$tenant->id}";

        $events = Cache::remember($cacheKey, 60 * 30, function () use ($lang, $tenant, $allTenants) {
            $query = Calendar::query()
                ->with(['category', 'media', 'tenant:id,shortname'])
                ->where('is_draft', false)
                ->orderByDesc('date')
                ->take(100);

            if ($lang === 'en') {
                $query->where('is_international', true);
            } elseif (! $allTenants) {
                $query->where('tenant_id', $tenant->id);
            }

            return $query->get()->map(function ($event) {
                $event->images = $event->getMedia('images');

                return $event;
            });
        });

        return $this->jsonSuccess($events);
    }
}
