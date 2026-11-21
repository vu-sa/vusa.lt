<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexResourceCategoryRequest;
use App\Http\Requests\StoreResourceCategoryRequest;
use App\Http\Requests\UpdateResourceCategoryRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\ResourceCategory;
use App\Services\TanstackTableService;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

class ResourceCategoryController extends AdminController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', ResourceCategory::class)]
    public function index(IndexResourceCategoryRequest $request)
    {
        $query = ResourceCategory::query();

        $searchableColumns = ['name'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
        );

        $resourceCategories = $query->paginate($request->getPerPage())
            ->withQueryString();

        return Inertia::render('Admin/Reservations/IndexResourceCategory', [
            'abilities' => [
                'update' => $request->user()->can('update', new ResourceCategory),
                'delete' => $request->user()->can('delete', new ResourceCategory),
            ],
            'resourceCategories' => [
                'data' => $resourceCategories->getCollection()->map(function ($category) {
                    /** @var ResourceCategory $category */
                    return $category->toFullArray();
                })->values(),
                'meta' => [
                    'total' => $resourceCategories->total(),
                    'per_page' => $resourceCategories->perPage(),
                    'current_page' => $resourceCategories->currentPage(),
                    'last_page' => $resourceCategories->lastPage(),
                    'from' => $resourceCategories->firstItem(),
                    'to' => $resourceCategories->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', ResourceCategory::class)]
    public function store(StoreResourceCategoryRequest $request)
    {
        $resourceCategory = new ResourceCategory;

        $validatedData = $request->safe();
        $resourceCategory->fill($validatedData->toArray());
        $resourceCategory->save();

        return redirect()->route('resourceCategories.index')->with(['success' => $this->entityMessage('created', 'resourceCategory')]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'resourceCategory')]
    public function update(UpdateResourceCategoryRequest $request, ResourceCategory $resourceCategory)
    {
        $validatedData = $request->safe();
        $resourceCategory->fill($validatedData->toArray());
        $resourceCategory->save();

        return back()->with(['success' => $this->entityMessage('updated', 'resourceCategory')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'resourceCategory')]
    public function destroy(ResourceCategory $resourceCategory)
    {
        $resourceCategory->delete();

        return redirect()->route('resourceCategories.index')->with(['success' => $this->entityMessage('deleted', 'resourceCategory')]);
    }
}
