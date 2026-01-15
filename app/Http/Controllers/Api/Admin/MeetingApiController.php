<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\ResourceServices\DutyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MeetingApiController extends ApiController
{
    /**
     * Get recent meetings for the current user's institutions.
     * Used by NewMeetingModal for quick meeting selection.
     *
     * @route GET /api/v1/admin/meetings/recent
     *
     * @routeName api.v1.admin.meetings.recent
     */
    public function recent(): JsonResponse
    {
        if (! Auth::check()) {
            return $this->jsonError('Unauthorized', 401);
        }

        $userInstitutions = DutyService::getUserInstitutionsForDashboard();

        if ($userInstitutions->isEmpty()) {
            return $this->jsonSuccess([]);
        }

        $sixMonthsAgo = now()->subMonths(6);

        $recentMeetings = $userInstitutions
            ->flatMap(function ($institution) use ($sixMonthsAgo) {
                return $institution->meetings
                    ?->filter(fn ($meeting) => $meeting->start_time >= $sixMonthsAgo)
                    ->map(fn ($meeting) => [
                        'id' => (string) $meeting->id,
                        'title' => $meeting->title,
                        'start_time' => $meeting->start_time?->toISOString(),
                        'institution_id' => (string) $institution->id,
                        'institution_name' => $institution->name ?? 'Unknown',
                        'agenda_items' => $meeting->agendaItems->map(fn ($item) => ['title' => $item->title])->toArray(),
                    ]) ?? collect();
            })
            ->sortByDesc('start_time')
            ->unique('id')
            ->take(10)
            ->values()
            ->toArray();

        return $this->jsonSuccess($recentMeetings);
    }
}
