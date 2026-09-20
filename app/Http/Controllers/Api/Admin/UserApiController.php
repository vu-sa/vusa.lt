<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildUserIndexQuery;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexUserRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\User;
use App\Services\TanstackTableService;
use Illuminate\Http\JsonResponse;

class UserApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', User::class);

        $users = $this->applyTanstackFilters(
            BuildUserIndexQuery::execute(),
            $request,
            $this->tableService,
            ['name', 'email', 'phone'],
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenants',
                'permission' => 'users.read.padalinys',
            ],
        )->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $users->getCollection()
                ->each->makeVisible(['last_action'])
                ->map(fn (User $user): array => $user->toArray())
                ->values(),
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
        ]);
    }
}
