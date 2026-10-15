<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildTaskIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexTasksRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\ModelAuthorizer;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;

class TaskApiController extends ApiController
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    /**
     * The task collection's refreshes (search, filters, "Rodyti daugiau") for both scopes.
     */
    public function index(IndexTasksRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);
        $scope = $request->validated('scope') ?? BuildTaskIndexQuery::SCOPE_MINE;

        if ($scope === BuildTaskIndexQuery::SCOPE_TENANT) {
            $this->authorizeApi('viewAny', Task::class);
        }

        $query = fn (IndexTasksRequest $request) => BuildTaskIndexQuery::execute($request, $scope, $user, $this->authorizer);
        $tasks = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $tasks->getCollection()->map(fn (Task $task) => TaskResource::forListing($task, $user))->values(),
            'total' => $tasks->total(),
            'per_page' => $tasks->perPage(),
            'current_page' => $tasks->currentPage(),
            'last_page' => $tasks->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['completion', 'taskable_type', 'tenant', 'overdue', 'auto', 'assigned'], $query),
        ]);
    }
}
