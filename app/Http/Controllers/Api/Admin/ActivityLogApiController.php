<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ActivityLogIndexRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\AgendaItemNote;
use App\Models\Pivots\AgendaItem;
use App\Models\Step;
use App\Models\Vote;
use App\Services\ActivityChangeFormatter;
use App\Services\AgendaItemPresenter;
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
            ->with(['causer:id,name,profile_photo_path', 'subject'])
            ->orderByDesc('id');

        // cursorPaginate() resolves the current cursor itself from the
        // request's "cursor" query parameter -- no manual decoding needed.
        $activities = $query->cursorPaginate((int) $request->validated('per_page', 25));

        $visible = collect($activities->items())->filter(function (Activity $activity) use ($request): bool {
            $child = $activity->subject;
            if ($child === null && in_array($activity->subjectClass(), [AgendaItem::class, AgendaItemNote::class, Vote::class, Step::class], true)) {
                return $request->user()->isSuperAdmin();
            }

            $item = match (true) {
                $child instanceof AgendaItem => $child,
                $child instanceof Vote, $child instanceof AgendaItemNote, $child instanceof Step => $child->agendaItem,
                default => null,
            };

            return $item === null || ! $item->is_private || AgendaItemPresenter::canRead($item, $request->user());
        })->values();

        $this->formatter->prepare($visible);

        return $this->jsonCursorPaginated($activities, ActivityResource::collection($visible));
    }
}
