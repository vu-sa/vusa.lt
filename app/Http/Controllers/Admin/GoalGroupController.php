<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoalGroup;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalGroupController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', GoalGroup::class);

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
        $this->authorize('create', GoalGroup::class);

        return Inertia::render('Admin/Representation/CreateGoalGroup');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', GoalGroup::class);

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
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(GoalGroup $goalGroup)
    {
        $this->authorize('update', $goalGroup);

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
        $this->authorize('update', $goalGroup);

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
        $this->authorize('delete', $goalGroup);

        $goalGroup->delete();

        return redirect()->route('goalGroups.index');
    }

    /**
     * Restore the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(GoalGroup $goalGroup)
    {
        $this->authorize('restore', $goalGroup);

        $goalGroup->restore();

        return back()->with('success', 'Tikslo grupė atkurta!');
    }
}
