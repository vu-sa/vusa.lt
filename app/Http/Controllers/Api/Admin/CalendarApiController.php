<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildCalendarIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexCalendarRequest;
use App\Models\Calendar;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class CalendarApiController extends ApiController
{
    public function __construct(private readonly TanstackTableService $tableService) {}

    #[Authorize('viewAny', Calendar::class)]
    public function index(IndexCalendarRequest $request): JsonResponse
    {
        $query = fn (IndexCalendarRequest $request) => BuildCalendarIndexQuery::execute($request, $this->tableService);
        $events = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $events->getCollection()->map(fn (Calendar $event): array => $event->toFullArray())->values(),
            'total' => $events->total(),
            'per_page' => $events->perPage(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['event_type_id', 'is_draft', 'untyped'], $query),
        ]);
    }
}
