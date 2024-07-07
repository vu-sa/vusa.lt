<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreResourceCategoryRequest;
use App\Http\Requests\UpdateResourceCategoryRequest;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Services\ModelIndexer;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Inertia\Inertia;

class ResourceCategoryController extends LaravelResourceController
{
    public function __construct()
    {
        parent::__construct();

        // TODO: precognition may not work well with file uploads, also when array is made of simple object and file upload
        // $this->middleware([HandlePrecognitiveRequests::class])->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Resource::class, $this->authorizer]);

        $indexer = new ModelIndexer(new ResourceCategory(), request(), $this->authorizer);

        $resourceCategories = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return Inertia::render('Admin/Reservations/IndexResourceCategory', [
            'resourceCategories' => $resourceCategories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Resource::class, $this->authorizer]);

        return Inertia::render('Admin/Reservations/CreateResourceCategory');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceCategoryRequest $request)
    {
        $resourceCategory = new ResourceCategory();

        $resourceCategory->fill($request->safe()->toArray());
        $resourceCategory->save();

        return redirect()->route('resourceCategories.index')->with(['success' => 'Resource category created successfully!']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResourceCategory $resourceCategory)
    {
        $this->authorize('create', [Resource::class, $this->authorizer]);

        return Inertia::render('Admin/Reservations/EditResourceCategory', [
            'resourceCategory' => $resourceCategory->toFullArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceCategoryRequest $request, ResourceCategory $resourceCategory)
    {

        $resourceCategory->fill($request->safe()->toArray());
        $resourceCategory->save();

        return back()->with(['success' => 'Resource category updated successfully!']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResourceCategory $resourceCategory)
    {
        $this->authorize('create', [Resource::class, $this->authorizer]);

        $resourceCategory->delete();

        return redirect()->route('resourceCategories.index')->with(['success' => 'Resource category deleted successfully!']);
    }
}
