<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexPermissionRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Permission;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;

class PermissionController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexPermissionRequest $request): \Inertia\Response
    {
        $this->handleAuthorization('viewAny', Permission::class);

        $query = Permission::query();

        $searchableColumns = ['name'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        $permissions = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        $sorting = $request->getSorting();

        return $this->inertiaResponse('Admin/Permissions/IndexPermission', [
            'permissions' => [
                'data' => $permissions->items(),
                'meta' => [
                    'total' => $permissions->total(),
                    'per_page' => $permissions->perPage(),
                    'current_page' => $permissions->currentPage(),
                    'last_page' => $permissions->lastPage(),
                    'from' => $permissions->firstItem(),
                    'to' => $permissions->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
        ]);
    }
}
