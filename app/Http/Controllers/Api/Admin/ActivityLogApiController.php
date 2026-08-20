<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ActivityLogIndexRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\ActivityChangeFormatter;
use App\Support\Auditables;
use App\Support\MorphMap;
use Illuminate\Http\JsonResponse;

/**
 * Serves a subject's activity-log feed for the ActivityLogViewer frontend.
 * Read follows the same "see it -> audit it" rule as the discussion API
 * (CommentApiController): a change history is strictly weaker information
 * than the record it describes, so it authorizes against the subject's own
 * `view` ability rather than inventing an unseeded `audit` permission.
 */
class ActivityLogApiController extends ApiController
{
    public function __construct(protected ActivityChangeFormatter $formatter) {}

    /**
     * Cursor-paginated activity feed for a subject. By default returns the
     * whole tree (see App\Support\ActivityRoots) rooted at the subject;
     * `scope=self` narrows to just the subject's own activities.
     */
    public function index(ActivityLogIndexRequest $request, string $subjectType, string $subjectId): JsonResponse
    {
        $subject = Auditables::resolve($subjectType, $subjectId);

        abort_if($subject === null, 404, 'Auditable subject not found.');

        $this->authorize('view', $subject);

        $scope = $request->validated('scope', 'tree');

        $query = Activity::query()
            ->when(
                $scope === 'self',
                fn ($q) => $q->whereMorphedTo('subject', $subject),
                fn ($q) => $q->forRoot($subject->getMorphClass(), (string) $subject->getKey()),
            )
            ->when($request->validated('event'), fn ($q, $event) => $q->where('event', $event))
            ->when(
                $request->validated('subject_type'),
                fn ($q, $type) => $q->where('subject_type', MorphMap::alias(Auditables::subjectClassFor($type)))
            )
            ->when($request->validated('causer_id'), fn ($q, $causerId) => $q->where('causer_id', $causerId))
            ->with('causer:id,name,profile_photo_path')
            ->orderByDesc('id');

        // cursorPaginate() resolves the current cursor itself from the
        // request's "cursor" query parameter -- no manual decoding needed.
        $activities = $query->cursorPaginate((int) $request->validated('per_page', 25));

        $this->formatter->prepare(collect($activities->items()));

        return $this->jsonCursorPaginated($activities, ActivityResource::collection($activities->items()));
    }
}
