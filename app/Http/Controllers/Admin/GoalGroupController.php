<?php

namespace App\Http\Controllers\Admin;

use App\Models\GoalGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Institution::class, $this->authorizer]);
        $goalGroups = GoalGroup::all()->paginate();

        return Inertia::render('Admin/Representation/IndexGoalGroups', [
            'goalGroups' => $goalGroups,
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GoalGroup  $goalGroup
     * @return \Illuminate\Http\Response
     */
    public function show(GoalGroup $goalGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GoalGroup  $goalGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(GoalGroup $goalGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\GoalGroup  $goalGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GoalGroup $goalGroup)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GoalGroup  $goalGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy(GoalGroup $goalGroup)
    {
        //
    }
}
