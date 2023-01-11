<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doing;
use App\Http\Controllers\Controller as Controller;
use App\Services\DoingStatusManager;
use App\Services\SharepointAppGraph;
use App\Services\TaskCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Microsoft\Graph\Model;
use Inertia\Inertia;

class DoingController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Doing::class, 'doing');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Institution::class, $this->authorizer]);
        $search = request()->input('search');

        $doings = Doing::with('matters')->when(!request()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%");
        });

        $unpaginatedDoings = $doings->get();

        // pluck all matters 
        $paginatedDoings = $doings->paginate(20);

        $matters = $unpaginatedDoings->pluck('matters')->flatten()->unique('id')->values();

        return Inertia::render('Admin/Representation/IndexDoing', [
            'doings' => $paginatedDoings,
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
        $request->validate([
            'title' => 'required',
            'type_id' => 'required',
            'user_id' => 'required'
        ]);
    
        $doing = Doing::create
            ($request->only('title') + ['status' => 'Sukurtas', 'date' => $request->date ?? now()]);
        
        $doing->types()->sync($request->type_id);
        $doing->users()->sync($request->user_id);

        DoingStatusManager::generateStatusForNewDoing($doing);
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
        $sharepointFiles = [];
        
        if ($doing->documents->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $sharepointFiles = $graph->collectSharepointFiles($doing->documents);
        }

        $doing->load('activities.causer', 'tasks', 'comments', 'doables', 'users');

        return Inertia::render('Admin/Representation/ShowDoing', [
            'matter' => $doing->matters->first()?->load('institution'),
            'doing' => $doing,
            'sharepointFiles' => $sharepointFiles,
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
        //
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
        $request->validate([
            'title' => 'required',
        ]);

        $doing->update($request->only('title', 'status', 'date'));

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
