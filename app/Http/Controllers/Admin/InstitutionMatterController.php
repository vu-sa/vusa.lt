<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use App\Models\InstitutionMatter as Matter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Type;
use App\Models\Doing;
use App\Models\Goal;

class InstitutionMatterController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Matter::class, 'matter');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $search = request()->input('search');

        $matters = Matter::when(!request()->user()->hasRole('Super Admin'), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%");
        })->paginate(20);

        return Inertia::render('Admin/Representation/IndexMatter', [
            'matters' => $matters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
        ]);

        // add status "Sukurtas" to validated
        // $validated['status'] = 'Sukurtas';
        $validated['institution_id'] = $request->institution_id;
        // if no matter group, create one
        // if (is_null($request->goal_id)) {
        //     $goal = Goal::create([
        //         'title' => 'Klausimo \"' . $validated['title'] . '\" grupė',
        //     ]);
        //     $validated['goal_id'] = $goal->id;
        // } else {
        //     // ...
        // }

        $matter = Matter::create($validated);

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
        $matter = $matter->load('institution', 'goals', 'activities', 'activities.causer');
        
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Matter  $matter
     * @return \Illuminate\Http\Response
     */
    public function destroy(Matter $matter)
    {
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
