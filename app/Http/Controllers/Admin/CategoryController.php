<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Page::class, $this->authorizer]);

        $categories = Category::all()->paginate(20);

        return Inertia::render('Admin/Content/IndexCategory', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Page::class, $this->authorizer]);

        return Inertia::render('Admin/Content/CreateCategory');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Page::class, $this->authorizer]);

        $request->validate([
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'description' => 'string|max:255',
        ]);

        Category::create($request->all());

        return redirect()->route('categories.index')->with('success', 'Kategorija sukurta.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $this->authorize('update', [Page::class, $this->authorizer]);

        return Inertia::render('Admin/Content/EditCategory', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $this->authorize('update', [Page::class, $this->authorizer]);

        $request->validate([
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'description' => 'string|max:255',
        ]);

        $category->update($request->all());

        return redirect()->route('categories.index')->with('success', 'Kategorija atnaujinta.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete', [Page::class, $this->authorizer]);

        Category::destroy($id);

        return redirect()->route('categories.index')->with('success', 'Kategorija ištrinta.');
    }
}
