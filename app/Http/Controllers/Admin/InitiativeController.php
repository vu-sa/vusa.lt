<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Http\Requests\StoreInitiativeRequest;
use App\Http\Requests\UpdateInitiativeRequest;
use App\Models\Initiative;
use App\Services\ModelIndexer;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InitiativeController extends ResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Initiative::class, $this->authorizer]);

        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $initiatives = $indexer->execute(Initiative::class, $search, 'title', $this->authorizer, null);

        return Inertia::render('Admin/Calendar/IndexInitiatives', [
            'initiatives' => $initiatives->orderBy('start_date', 'desc')->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Initiative::class, $this->authorizer]);

        return Inertia::render('Admin/Calendar/CreateInitiative');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInitiativeRequest $request)
    {
        $validated = $request->validated();

        $initiative = new Initiative();

        // create slug
        $slug = Str::slug($validated['title']['lt'], '-', 'lt');

        $initiative->fill($validated + ['slug' => $slug]);
        $initiative->save();

        return redirect()->route('initiatives.index')->with('success', 'Iniciatyva sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Initiative $initiative)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Initiative $initiative)
    {
        $this->authorize('update', [Initiative::class, $this->authorizer]);

        return Inertia::render('Admin/Calendar/EditInitiative', [
            'initiative' => array_merge($initiative->toArray(), $initiative->getTranslations()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInitiativeRequest $request, Initiative $initiative)
    {
        $validated = $request->validated();

        // create slug
        $initiative->fill($validated);
        $initiative->save();

        return redirect()->back()->with('success', 'Iniciatyva sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Initiative $initiative)
    {
        //
    }
}
