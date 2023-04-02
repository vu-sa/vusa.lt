<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\GoalGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalGroupController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [GoalGroup::class, $this->authorizer]);

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
        $this->authorize('create', [GoalGroup::class, $this->authorizer]);

        return Inertia::render('Admin/Representation/CreateGoalGroup');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [GoalGroup::class, $this->authorizer]);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        GoalGroup::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('goalGroups.index');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(GoalGroup $goalGroup)
    {
        return $this->authorize('view', [
            GoalGroup::class,
            $goalGroup,
            $this->authorizer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(GoalGroup $goalGroup)
    {
        $this->authorize('update', [GoalGroup::class, $goalGroup, $this->authorizer]);

        return Inertia::render('Admin/Representation/EditGoalGroup', [
            'goalGroup' => $goalGroup,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GoalGroup $goalGroup)
    {
        $this->authorize('update', [GoalGroup::class, $goalGroup, $this->authorizer]);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        $goalGroup->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('goalGroups.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(GoalGroup $goalGroup)
    {
        $this->authorize('delete', [GoalGroup::class, $goalGroup, $this->authorizer]);

        $goalGroup->delete();

        return redirect()->route('goalGroups.index');
    }
}
