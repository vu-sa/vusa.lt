<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doing;
use App\Http\Controllers\Controller as Controller;
use App\Services\DoingStatusManager;
use App\Services\SharepointAppGraph;
use App\Services\TaskCreator;
use Illuminate\Http\Request;
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

        $doings = Doing::with('questions')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('title', 'like', "%{$search}%");
        });

        $unpaginatedDoings = $doings->get();

        // pluck all questions 
        $paginatedDoings = $doings->paginate(20);

        $questions = $unpaginatedDoings->pluck('questions')->flatten()->unique('id')->values();

        return Inertia::render('Admin/Questions/IndexDoings', [
            'doings' => $paginatedDoings,
            'questions' => $questions,
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

        if (!is_null($request->input('questionForm'))) {
            // parse 'titlesOrIds' for new questions
            foreach ($request->input('questionForm')['titlesOrIds'] as $value) {
                switch (gettype($value)) {
                    case 'integer':
                        $doing->questions()->attach($value);
                        break;
                    case 'string':
                        $doing->questions()->create([
                            'title' => $value,
                            'description' => $request->input('questionForm')['description'],
                            'status' => 'Sukurtas',
                            'institution_id' => $request->input('questionForm')['institution_id']
                        ]);

                        if (!is_null($request->input('questionForm')['andOther'] ?? null)) {
                            $doing->extra_attributes = ['andOther' => $request->input('questionForm')['andOther']];
                            $doing->save();
                        }
                        break;
                    }
                }
        } else {
            $doing->questions()->sync($request->question_id);
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

        return Inertia::render('Admin/Questions/ShowDoing', [
            'question' => $doing->questions->first()?->load('institution'),
            'doing' => [
                    ...$doing->toArray(),
                    'activities' => $doing->activities->map(function ($activity) {
                        return [
                            ...$activity->toArray(),
                            // 'date' => $activity->date->format('Y-m-d'),
                            'causer' => $activity->causer
                        ];
                    }),
                    'tasks' => $doing->tasks,
                    'comments' => $doing->comments->reverse()->values(),
                ],
            'documents' => $sharepointFiles,
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
        // detach doings from questions
        $doing->questions()->detach();

        // detach doings from types
        $doing->types()->detach();

        // delete tasks
        $doing->tasks()->delete();

        // delete comments
        $doing->comments()->delete();

        // delete doing
        $doing->delete();

        return redirect()->route('doings.index')->with('success', 'Veikla ištrinta!');
    }
}
