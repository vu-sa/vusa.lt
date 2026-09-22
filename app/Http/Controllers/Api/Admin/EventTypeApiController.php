<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexEventTypeRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\EventType;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class EventTypeApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexEventTypeRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', EventType::class);

        $query = EventType::query();

        if ($request->getShowDeleted()) {
            $query = $this->withForceDeleteBlockers($query, $request, ['calendarEvents']);
        }

        $eventTypes = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['name', 'slug'],
            [
                'applySortBeforePagination' => true,
            ]
        )->paginate($request->getPerPage());

        if ($request->getShowDeleted()) {
            $this->appendForceDeleteBlockedReason($eventTypes->getCollection(), $request);
        }

        return $this->jsonSuccess([
            'items' => $eventTypes->getCollection()->map(fn (EventType $eventType): array => $eventType->append('force_delete_blocked_reason')->toFullArray())->values(),
            'total' => $eventTypes->total(),
            'per_page' => $eventTypes->perPage(),
            'current_page' => $eventTypes->currentPage(),
            'last_page' => $eventTypes->lastPage(),
        ]);
    }
}
