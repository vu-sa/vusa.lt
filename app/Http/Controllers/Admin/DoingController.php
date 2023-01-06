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
        $search = request()->input('search');

        $doings = Doing::with('matters')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
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
        ]);
    
        $doing = Doing::create
            ($request->only('title') + ['status' => 'Sukurtas', 'date' => $request->date ?? now()]);
        
        $doing->types()->sync($request->type_id);

        // TODO: This is not needed anymore, as it's transferred to matters

        if (!is_null($request->input('matterForm'))) {
            // parse 'titlesOrIds' for new matters
            foreach ($request->input('matterForm')['titlesOrIds'] as $value) {
                switch (gettype($value)) {
                    case 'integer':
                        $doing->matters()->attach($value);
                        break;
                    case 'string':
                        $doing->matters()->create([
                            'title' => $value,
                            'description' => $request->input('matterForm')['description'],
                            'status' => 'Sukurtas',
                            'institution_id' => $request->input('matterForm')['institution_id']
                        ]);

                        if (!is_null($request->input('matterForm')['andOther'] ?? null)) {
                            $doing->extra_attributes = ['andOther' => $request->input('matterForm')['andOther']];
                            $doing->save();
                        }
                        break;
                    }
                }
        } else {
            $doing->matters()->sync($request->matter_id);
        }

        DoingStatusManager::generateStatusForNewDoing($doing);
        TaskCreator::createAutomaticTasks($doing);

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
