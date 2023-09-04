<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Doing;
use App\Models\Goal;
use App\Models\Matter;
use App\Models\Type;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MatterController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Matter::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Matter(), request(), $this->authorizer);

        $matters = $indexer
            ->setEloquentQuery([
                fn (Builder $query) => $query->with(['institutions:id,name,short_name']),
            ])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return Inertia::render('Admin/Representation/IndexMatter', [
            'matters' => $matters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function show(Matter $matter)
    {
        $this->authorize('view', [Matter::class, $matter, $this->authorizer]);

        $matter = $matter->load('institutions', 'doings', 'goals', 'activities.causer');

        return Inertia::render('Admin/Representation/ShowMatter', [
            'matter' => $matter,
            'doingTypes' => Type::where('model_type', Doing::class)->get(['id', 'title']),
            'goals' => Inertia::lazy(fn () => Goal::get(['id', 'title'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Matter $matter)
    {
        $this->authorize('delete', [Matter::class, $matter, $this->authorizer]);

        // * Don't detach because of soft-deletes
        // delete doing_matter records
        // $matter->doings()->detach();

        // delete matter
        $matter->delete();

        return redirect()->route('matters.index')->with('success', 'Klausimas sėkmingai ištrintas');
    }

    public function restore(Matter $matter)
    {
        $this->authorize('restore', [Matter::class, $matter, $this->authorizer]);

        $matter->restore();

        return back()->with('success', 'Klausimas atkurtas!');
    }

    public function attachGoal(Matter $matter, Request $request)
    {
        $this->authorize('update', [Matter::class, $matter, $this->authorizer]);

        $validated = $request->validate([
            'goal_id' => 'required',
        ]);

        // dd($validated);
        // get padalinys from first matter institution
        $padalinys = $matter->institutions->first()->padalinys;

        // check if goal_id exists if not, create new with that name
        $goal = Goal::firstOrNew(['id' => $validated['goal_id']], ['title' => $validated['goal_id'], 'padalinys_id' => $padalinys->id, 'start_date' => now()]);
        // generate uuid
        $goal->id = (string) \Illuminate\Support\Str::ulid();
        $goal->save();

        $matter->goals()->sync($goal);

        return back()->with('success', 'Svarstomas klausimas sėkmingai pridėtas prie tikslo');
    }
}
