<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Dutiables\BuildDutiableTimeline;
use App\Actions\Dutiables\PlanDutiableTimelineChanges;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\DutiableTimelinePreviewRequest;
use App\Http\Requests\Api\Admin\DutiableTimelineRequest;
use Illuminate\Http\JsonResponse;

/**
 * Feeds the dutiable timeline editor: the chart payload, and the dry run behind the
 * "peržiūrėti" step. Both are read-only — every mutation goes through
 * DutiableTimelineController, which needs the Inertia flash contract that
 * AdminController::guardSelfLockout() depends on.
 *
 * @routeName api.v1.admin.dutiableTimeline.index
 * @routeName api.v1.admin.dutiableTimeline.preview
 */
class DutiableTimelineApiController extends ApiController
{
    public function index(DutiableTimelineRequest $request): JsonResponse
    {
        $actor = $this->requireAuth($request);

        return $this->jsonSuccess(BuildDutiableTimeline::execute(
            $request->validated('scope'),
            $request->validated('scope_id'),
            $actor,
            $request->validated('duty_ids') ?? [],
            $request->boolean('include_ended', true),
        ));
    }

    /**
     * Plans the staged operations without writing anything, so the user confirms a
     * concrete before → after rather than a promise.
     */
    public function preview(DutiableTimelinePreviewRequest $request): JsonResponse
    {
        $actor = $this->requireAuth($request);

        $plan = PlanDutiableTimelineChanges::execute($request->operations());

        return $this->jsonSuccess(array_merge($plan->toArray(), [
            // Telegraphs the guardSelfLockout prompt the commit will raise, rather than
            // letting it ambush the user after they press save.
            'self_affecting' => ! $actor->isSuperAdmin() && $plan->touchesUser($actor->id),
        ]));
    }
}
