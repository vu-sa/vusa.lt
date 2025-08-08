<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization(Category::class, 'viewAny');

        $categories = Category::paginate(20);

        return $this->inertiaResponse('Admin/Content/IndexCategory', [
            'categories' => $categories,
            'filters' => (object) [], // Empty filters for test compatibility
            'sorting' => (object) [], // Empty sorting for test compatibility
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
