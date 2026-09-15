<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexEventTypeRequest;
use App\Http\Requests\StoreEventTypeRequest;
use App\Http\Requests\UpdateEventTypeRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\EventType;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class EventTypeController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexEventTypeRequest $request): Response
    {
        $this->handleAuthorization('viewAny', EventType::class);

        $query = EventType::query();

        $searchableColumns = ['name', 'slug'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        $deletedCount = $this->getTrashedCount($query);

        // Trash view only: lets the table say why permanent deletion is refused.
        $query = $this->withForceDeleteBlockers($query, $request, ['calendarEvents']);

        $eventTypes = $query->paginate($request->getPerPage())
            ->withQueryString();

        $this->appendForceDeleteBlockedReason($eventTypes->getCollection(), $request);

        return $this->inertiaResponse('Admin/Calendar/IndexEventType', [
            'eventTypes' => [
                'data' => $eventTypes->getCollection()
                    ->map(function ($eventType) {
                        /** @var EventType $eventType */
                        return $eventType->toFullArray();
                    }),
                'meta' => [
                    'total' => $eventTypes->total(),
                    'per_page' => $eventTypes->perPage(),
                    'current_page' => $eventTypes->currentPage(),
                    'last_page' => $eventTypes->lastPage(),
                    'from' => $eventTypes->firstItem(),
                    'to' => $eventTypes->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->handleAuthorization('create', EventType::class);

        return $this->inertiaResponse('Admin/Calendar/CreateEventType');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventTypeRequest $request): RedirectResponse
    {
        EventType::create($request->validated());

        return $this->redirectToIndexWithSuccess('eventTypes', $this->entityMessage('created', 'eventType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventType $eventType): Response
    {
        $this->handleAuthorization('update', $eventType);

        return $this->inertiaResponse('Admin/Calendar/EditEventType', [
            'eventType' => $eventType->toFullArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventTypeRequest $request, EventType $eventType): RedirectResponse
    {
        $eventType->update($request->validated());

        return $this->redirectToIndexWithSuccess('eventTypes', $this->entityMessage('updated', 'eventType'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventType $eventType): RedirectResponse
    {
        $this->handleAuthorization('delete', $eventType);

        $eventType->delete();

        return $this->redirectToIndexWithSuccess('eventTypes', $this->entityMessage('deleted', 'eventType'));
    }

    public function restore(EventType $eventType): RedirectResponse
    {
        return $this->restoreModel($eventType);
    }

    public function forceDelete(EventType $eventType): RedirectResponse
    {
        return $this->forceDeleteModel($eventType);
    }
}
