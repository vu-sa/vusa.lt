<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;

class InstitutionApiController extends ApiController
{
    /**
     * Lightweight institution detail used by the admin search preview pane.
     * Returns the institution types, recent meetings and current
     * representatives that are not carried in the Typesense search document.
     *
     * @route GET /api/v1/admin/institutions/{institution}/preview
     *
     * @routeName api.v1.admin.institutions.preview
     */
    public function preview(Institution $institution): JsonResponse
    {
        $this->authorizeApi('view', $institution);

        $institution->load([
            'types',
            'duties.current_users',
        ]);

        $meetings = $institution->meetings()
            ->orderByDesc('start_time')
            ->limit(5)
            ->get(['meetings.id', 'title', 'start_time']);

        return $this->jsonSuccess([
            'types' => $institution->types
                ->map(fn ($type) => [
                    'id' => (string) $type->id,
                    'title' => $type->title,
                ])->values(),
            'last_meetings' => $meetings
                ->map(fn ($meeting) => [
                    'id' => (string) $meeting->id,
                    'title' => $meeting->title,
                    'start_time' => $meeting->start_time?->timestamp,
                ])->values(),
            'representatives' => $institution->duties
                ->flatMap->current_users
                ->unique('id')
                ->map(fn ($user) => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'profile_photo_path' => $user->profile_photo_path,
                ])->values(),
        ]);
    }
}
