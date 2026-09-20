<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexResourceCategoryRequest;
use App\Http\Requests\StoreResourceCategoryRequest;
use App\Http\Requests\UpdateResourceCategoryRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\ResourceCategory;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;

class ResourceCategoryController extends AdminController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexResourceCategoryRequest $request)
    {
        $this->handleAuthorization('viewAny', ResourceCategory::class);

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

        return $this->inertiaResponse('Admin/Reservations/IndexResourceCategory', [
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
     * Categories are created in a sheet over the collection (Phase 9.2).
     *
     * @deprecated Kept for bookmarks; remove in Phase 10.
     */
    public function create(): RedirectResponse
    {
        $this->handleAuthorization('create', ResourceCategory::class);

        return redirect()->route('resourceCategories.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceCategoryRequest $request)
    {
        $resourceCategory = new ResourceCategory;

        $validatedData = $request->safe();
        $resourceCategory->fill($validatedData->toArray());
        $resourceCategory->save();

        return redirect()->route('resourceCategories.index')->with(['success' => $this->entityMessage('created', 'resourceCategory')]);
    }

    /**
     * Categories are edited in a sheet over the collection (Phase 9.2).
     *
     * @deprecated Kept for bookmarks; remove in Phase 10.
     */
    public function edit(ResourceCategory $resourceCategory): RedirectResponse
    {
        $this->handleAuthorization('update', $resourceCategory);

        return redirect()->route('resourceCategories.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceCategoryRequest $request, ResourceCategory $resourceCategory)
    {
        $this->handleAuthorization('update', $resourceCategory);

        $validatedData = $request->safe();
        $resourceCategory->fill($validatedData->toArray());
        $resourceCategory->save();

        return back()->with(['success' => $this->entityMessage('updated', 'resourceCategory')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResourceCategory $resourceCategory)
    {
        $this->handleAuthorization('delete', $resourceCategory);

        $resourceCategory->delete();

        return redirect()->route('resourceCategories.index')->with(['success' => $this->entityMessage('deleted', 'resourceCategory')]);
    }
}
