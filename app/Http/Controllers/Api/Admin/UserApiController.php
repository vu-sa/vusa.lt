<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildUserIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexUserRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;

class UserApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService, private ModelAuthorizer $authorizer) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', User::class);

        $query = fn (IndexUserRequest $request) => $this->applyTanstackFilters(
            BuildUserIndexQuery::execute($request, $this->authorizer),
            $request,
            $this->tableService,
            ['name', 'email', 'phone'],
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenants',
                'permission' => 'users.read.padalinys',
                'handledFilters' => ['future_duty'],
            ],
        );

        $users = $this->withForceDeleteBlockers($query($request), $request)->paginate($request->getPerPage());
        $this->appendForceDeleteBlockedReason($users->getCollection(), $request);

        return $this->jsonSuccess([
            'items' => $users->getCollection()
                ->each->makeVisible(['last_action'])
                ->map(fn (User $user): array => $user->toArray())
                ->values(),
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['future_duty'], $query),
        ]);
    }
}
