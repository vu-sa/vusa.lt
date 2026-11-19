<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexEventTypeRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\EventType;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class EventTypeApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    #[Authorize('viewAny', EventType::class)]
    public function index(IndexEventTypeRequest $request): JsonResponse
    {
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
            'items' => $eventTypes->getCollection()->map(fn (EventType $eventType): array => $eventType->toFullArray())->values(),
            'total' => $eventTypes->total(),
            'per_page' => $eventTypes->perPage(),
            'current_page' => $eventTypes->currentPage(),
            'last_page' => $eventTypes->lastPage(),
        ]);
    }
}
