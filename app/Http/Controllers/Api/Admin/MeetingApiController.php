<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Meeting;
use App\Services\ResourceServices\DutyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MeetingApiController extends ApiController
{
    /**
     * Lightweight meeting detail used by the admin search preview pane.
     * Returns the agenda items, institutions and representatives that are not
     * carried in the Typesense search document.
     *
     * @route GET /api/v1/admin/meetings/{meeting}/preview
     *
     * @routeName api.v1.admin.meetings.preview
     */
    public function preview(Meeting $meeting): JsonResponse
    {
        $this->authorizeApi('view', $meeting);

        $meeting->load([
            'institutions',
            'agendaItems' => fn ($query) => $query->orderBy('order')->with('mainVote'),
        ]);

        return $this->jsonSuccess([
            'institutions' => $meeting->institutions
                ->map(fn ($institution) => [
                    'id' => (string) $institution->id,
                    'name' => $institution->name,
                ])->values(),
            'agenda_items' => $meeting->agendaItems
                ->map(fn ($item) => [
                    'id' => (string) $item->id,
                    'title' => $item->title,
                    'decision' => $item->mainVote?->decision,
                    'student_benefit' => $item->mainVote?->student_benefit,
                ])->values(),
            // Student representatives whose duty was active at the meeting date
            // (same calculation the meeting show page uses), not all-time members.
            'representatives' => $meeting->getRepresentativesActiveAt()
                ->map(fn ($user) => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'profile_photo_path' => $user->profile_photo_path,
                ])->values(),
        ]);
    }

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
