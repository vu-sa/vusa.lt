<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;
use App\Models\Matter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Type;
use App\Models\Doing;
use App\Models\Goal;
use App\Services\ModelIndexer;

class MatterController extends ResourceController
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Matter::class, $this->authorizer]);

        $search = request()->input('text');

        $matters = new ModelIndexer();
        $matters = $matters->execute(Matter::class, $search, 'title', $this->authorizer, 'padaliniai');

        return Inertia::render('Admin/Representation/IndexMatter', [
            'matters' => $matters->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Matter::class, $this->authorizer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Matter::class, $this->authorizer]);
        
        $validated = $request->validate([
            'title' => 'required',
        ]);

        $matter = Matter::create($validated);

        // attach institutions
        $matter->institutions()->attach($request->institution_id);
        $matter->goals()->attach($request->goal_id);

        // reminder to adjust matter creation in the frontend
        return redirect()->back()->with('data', $matter)->with('success', 'Klausimas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Http\Response
     */
    public function show(Matter $matter)
    {
        $this->authorize('view', [Matter::class, $matter, $this->authorizer]);
        
        
        $matter = $matter->load('institutions', 'goals', 'activities', 'activities.causer');

        return Inertia::render('Admin/Representation/ShowMatter', [
            'matter' => $matter,
            'doingTypes' => Type::where('model_type', Doing::class)->get()->map(function ($doingType) {
                return [
                    'value' => $doingType->id,
                    'label' => $doingType->title,
                ];
            }),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Http\Response
     */
    public function edit(Matter $matter)
    {
        $this->authorize('update', [Matter::class, $matter, $this->authorizer]);
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Matter $matter)
    {
        $this->authorize('update', [Matter::class, $matter, $this->authorizer]);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Http\Response
     */
    public function destroy(Matter $matter)
    {
        $this->authorize('delete', [Matter::class, $matter, $this->authorizer]);
        
        // delete doing_matter records
        $matter->doings()->detach();

        // delete matter
        $matter->delete();

        return redirect()->route('matters.index')->with('success', 'Klausimas sėkmingai ištrintas');
    }

    public function attachGoal(Matter $matter, Goal $goal, Request $request) {
        
        $matter->goals()->attach($goal);

        return back()->with('success', 'Svarstomas klausimas sėkmingai pridėtas prie tikslo');
    }
}
