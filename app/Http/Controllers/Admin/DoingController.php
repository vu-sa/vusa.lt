<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doing;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;
use App\Services\DoingStatusManager;
use App\Services\ModelIndexer;
use App\Services\SharepointAppGraph;
use App\Services\TaskCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Microsoft\Graph\Model;
use Inertia\Inertia;

class DoingController extends ResourceController
{    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Doing::class, $this->authorizer]);

        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $doings = $indexer->execute(Doing::class, $search, 'title', $this->authorizer);

        return Inertia::render('Admin/Representation/IndexDoing', [
            'doings' => $doings->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Doing::class, $this->authorizer]);

        return Inertia::render('Admin/Representation/CreateDoing');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Doing::class, $this->authorizer]);
        
        $request->validate([
            'title' => 'required',
            'user_id' => 'required'
        ]);
    
        $doing = Doing::create
            ($request->only('title') + ['status' => 'Sukurtas', 'date' => $request->date ?? now()]);
        
        // $doing->types()->sync($request->type_id);
        $doing->users()->sync($request->user_id);

        // DoingStatusManager::generateStatusForNewDoing($doing);
        // TaskCreator::createAutomaticTasks($doing);

        return redirect()->route('doings.show', $doing)->with('success', 'Veiksmas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function show(Doing $doing)
    {
        $this->authorize('view', [Doing::class, $doing, $this->authorizer]);

        $doing->load('activities.causer', 'tasks', 'comments', 'doables', 'users');

        return Inertia::render('Admin/Representation/ShowDoing', [
            'doing' => $doing,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function edit(Doing $doing)
    {
        $this->authorize('update', [Doing::class, $doing, $this->authorizer]);

        return Inertia::render('Admin/Representation/EditDoing', [
            'doing' => $doing->load('types', 'users'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Doing $doing)
    {
        $this->authorize('update', [Doing::class, $doing, $this->authorizer]);
        
        $request->validate([
            'title' => 'required',
            'user_id' => 'required',
            'date' => 'required'
        ]);

        $doing->update($request->only('title', 'date'));

        $doing->users()->sync($request->only('user_id'));

        return redirect()->route('doings.show', $doing)->with('success', 'Veikla atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Doing  $doing
     * @return \Illuminate\Http\Response
     */
    public function destroy(Doing $doing)
    {
        $this->authorize('delete', [Doing::class, $doing, $this->authorizer]);
        
        DB::transaction(function () use ($doing) {
            // detach doings from matters
            $doing->matters()->detach();

            // detach doings from types
            $doing->types()->detach();

            // delete tasks
            $doing->tasks()->delete();

            // delete comments
            $doing->comments()->delete();

            // delete doing
            $doing->delete();
        });

        return back()->with('success', 'Veikla ištrinta!');
    }
}
