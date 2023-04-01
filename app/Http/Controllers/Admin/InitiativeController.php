<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Http\Requests\StoreInitiativeRequest;
use App\Http\Requests\UpdateInitiativeRequest;
use App\Models\Initiative;
use App\Services\ModelIndexer;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInitiativeRequest $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInitiativeRequest $request, Initiative $initiative)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Initiative $initiative)
    {
        //
    }
}
