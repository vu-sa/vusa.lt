<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Category;
use App\Services\TanstackTableService;

class CategoryController extends AdminController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexCategoryRequest $request): \Inertia\Response
    {
        $this->handleAuthorization(Category::class, 'viewAny');

        $query = Category::query();

        $searchableColumns = ['name', 'alias'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        $categories = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        $sorting = $request->getSorting();

        return $this->inertiaResponse('Admin/Content/IndexCategory', [
            'categories' => [
                'data' => $categories->getCollection()
                    ->map(function ($category) {
                        /** @var \App\Models\Category $category */
                        return $category->toFullArray();
                    }),
                'meta' => [
                    'total' => $categories->total(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization(Category::class, 'create');

        return $this->inertiaResponse('Admin/Content/CreateCategory');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return $this->redirectToIndexWithSuccess('categories', 'Kategorija sukurta.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $this->handleAuthorization($category, 'view');

        return $this->inertiaResponse('Admin/Content/EditCategory', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return $this->redirectToIndexWithSuccess('categories', 'Kategorija atnaujinta.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $this->handleAuthorization($category, 'delete');

        $category->delete();

        return $this->redirectToIndexWithSuccess('categories', 'Kategorija ištrinta.');
    }
}
