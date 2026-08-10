<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Duty;
use App\Services\DutySimilarityFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Warns about an existing duty before a duplicate is created — the duty
 * equivalent of UserSearchApiController::similar().
 */
class DutySearchApiController extends ApiController
{
    /**
     * Find duties that look like the one about to be created (or renamed to).
     *
     * `institution_id` is what makes a same-institution match near-certain; without
     * it only the (looser, capped) other-institution tier is checked.
     * `exclude_id` omits the duty being edited from its own results.
     */
    public function similar(Request $request, DutySimilarityFinder $finder): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'institution_id' => 'nullable|string|ulid',
            'exclude_id' => 'nullable|string|ulid',
        ]);

        $actor = $this->requireAuth($request);

        if (! $actor->can('create', Duty::class)) {
            return $this->jsonForbidden();
        }

        $result = $finder->find(
            $request->string('name')->toString(),
            $request->string('institution_id')->toString() ?: null,
            $request->string('exclude_id')->toString() ?: null,
        );

        $mapMatch = fn (array $match) => [
            'id' => $match['duty']->id,
            'name' => $match['duty']->name,
            'reason' => $match['reason'],
            'institution_name' => $match['duty']->institution?->name,
            'tenant_shortname' => $match['duty']->institution?->tenant?->shortname,
            'current_holder_names' => $match['duty']->relationLoaded('current_users')
                ? $match['duty']->current_users->pluck('name')->take(2)->values()
                : [],
            'places_to_occupy' => $match['duty']->places_to_occupy,
            'can_manage' => $actor->can('update', $match['duty']),
        ];

        return $this->jsonSuccess([
            'same_institution' => $result['same_institution']->map($mapMatch)->values(),
            'other_institution' => $result['other_institution']->map($mapMatch)->values(),
            'other_institution_count' => $result['other_institution_count'],
        ]);
    }
}
