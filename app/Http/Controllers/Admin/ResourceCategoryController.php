<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreResourceCategoryRequest;
use App\Http\Requests\UpdateResourceCategoryRequest;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;

class ResourceCategoryController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Resource::class);

        $indexer = new ModelIndexer(new ResourceCategory);

        $resourceCategories = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/Reservations/IndexResourceCategory', [
            'resourceCategories' => $resourceCategories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Resource::class);

        return $this->inertiaResponse('Admin/Reservations/CreateResourceCategory');
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

        return redirect()->route('resourceCategories.index')->with(['success' => 'Resource category created successfully!']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResourceCategory $resourceCategory)
    {
        $this->handleAuthorization('create', Resource::class);

        return $this->inertiaResponse('Admin/Reservations/EditResourceCategory', [
            'resourceCategory' => $resourceCategory->toFullArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceCategoryRequest $request, ResourceCategory $resourceCategory)
    {

        $validatedData = $request->safe();
        $resourceCategory->fill($validatedData->toArray());
        $resourceCategory->save();

        return back()->with(['success' => 'Resource category updated successfully!']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResourceCategory $resourceCategory)
    {
        $this->handleAuthorization('create', Resource::class);

        $resourceCategory->delete();

        return redirect()->route('resourceCategories.index')->with(['success' => 'Resource category deleted successfully!']);
    }
}
