<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Category;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (! $this->authorizer->forUser(Auth::user())->checkAllRoleables('categories.read.*')) {
            abort(403);
        }

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
        if (! $this->authorizer->forUser(Auth::user())->checkAllRoleables('categories.create.*')) {
            abort(403);
        }

        return $this->inertiaResponse('Admin/Content/CreateCategory');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! $this->authorizer->forUser(Auth::user())->checkAllRoleables('categories.create.*')) {
            abort(403);
        }

        $request->validate([
            'name.lt' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'alias' => 'nullable|string|max:255|unique:categories,alias',
        ]);

        Category::create($request->all());

        return $this->redirectToIndexWithSuccess('categories', 'Kategorija sukurta.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        if (! $this->authorizer->forUser(Auth::user())->checkAllRoleables('categories.update.*')) {
            abort(403);
        }

        return $this->inertiaResponse('Admin/Content/EditCategory', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        if (! $this->authorizer->forUser(Auth::user())->checkAllRoleables('categories.update.*')) {
            abort(403);
        }

        $request->validate([
            'name.lt' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'alias' => 'nullable|string|max:255|unique:categories,alias,'.$category->id,
        ]);

        $category->update($request->all());

        return $this->redirectToIndexWithSuccess('categories', 'Kategorija atnaujinta.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (! $this->authorizer->forUser(Auth::user())->checkAllRoleables('categories.delete.*')) {
            abort(403);
        }

        Category::destroy($id);

        return $this->redirectToIndexWithSuccess('categories', 'Kategorija ištrinta.');
    }
}
