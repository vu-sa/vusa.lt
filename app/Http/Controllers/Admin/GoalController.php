<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\Goal;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoalController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Goal::class, $this->authorizer]);

        $indexer = new ModelIndexer();

        $search = request()->input('search');
        $goals = $indexer->execute(Goal::class, $search, 'title', $this->authorizer, null);

        return Inertia::render('Admin/Representation/IndexGoal', [
            'goals' => $goals->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Goal::class, $this->authorizer]);

        return Inertia::render('Admin/Representation/CreateGoal');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Goal::class, $this->authorizer]);

        $validated = $request->validate([
            'title' => 'required',
        ]);

        $goal = Goal::create($validated + ['start_date' => now()]);

        return back()->with('success', 'Tikslas sukurtas')->with('data', $goal->id);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Goal $goal)
    {
        $this->authorize('view', [Goal::class, $goal, $this->authorizer]);

        $goal->load('matters.doings', 'matters.institutions:id,name', 'activities.causer', 'comments')->load(['tasks' => function ($query) {
            $query->with('users', 'taskable');
        }]);

        $institutions = $goal->matters->pluck('institutions')->flatten()->unique('id');

        return Inertia::render('Admin/Representation/ShowGoal', [
            'goal' => $goal,
            'institutions' => $institutions->values(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Goal $goal)
    {
        $this->authorize('update', [Goal::class, $goal, $this->authorizer]);

        return Inertia::render('Admin/Representation/EditGoal', [
            'goal' => $goal,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Goal $goal)
    {
        $this->authorize('update', [Goal::class, $goal, $this->authorizer]);

        $validated = $request->validate(
            ['title' => 'required']
        );

        $goal->update($validated);

        return back()->with('success', 'Klausimo grupė atnaujinta');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Goal $goal)
    {
        $this->authorize('delete', [Goal::class, $goal, $this->authorizer]);

        $goal->delete();

        return redirect()->route('dashboard')->with('success', 'Klausimo grupė ištrinta.');
    }
}
