<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\GetFollowedInstitutions;
use App\Http\Controllers\Api\ApiController;
use App\Models\Duty;
use App\Models\Institution;
use App\Services\InstitutionAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandPaletteApiController extends ApiController
{
    public function personalRecords(Request $request, InstitutionAccessService $access): JsonResponse
    {
        $actor = $this->requireAuth($request);
        $assignedIds = $access->getUserDutyInstitutionIds($actor)->merge($access->getSecretariedInstitutionIds($actor));
        $followedIds = GetFollowedInstitutions::institutionIds($actor);

        $institutions = Institution::query()->whereKey($assignedIds->merge($followedIds)->unique())
            ->with('tenant:id,shortname')
            ->get()
            ->filter(fn (Institution $institution): bool => $actor->can('viewSummary', $institution))
            ->map(fn (Institution $institution): array => [
                'id' => (string) $institution->getKey(),
                'kind' => $assignedIds->contains($institution->getKey()) ? 'assigned' : 'followed',
                'title' => $institution->name,
                'context' => $institution->tenant?->shortname,
                'href' => route('institutions.show', $institution, false),
            ]);

        $duties = $actor->current_duties()->with('institution:id,name')->get()->unique('id')
            ->filter(fn (Duty $duty): bool => $actor->can('view', $duty))
            ->map(fn (Duty $duty): array => [
                'id' => (string) $duty->getKey(),
                'kind' => 'duty',
                'title' => $duty->name,
                'context' => $duty->institution?->name,
                'href' => route('duties.show', $duty, false),
            ]);

        return $this->jsonSuccess($institutions->concat($duties)->values()->all());
    }
}
