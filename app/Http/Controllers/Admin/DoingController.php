<?php

namespace App\Http\Controllers\Admin;

use App\Models\Doing;
use App\Http\Controllers\ResourceController;
use App\Http\Requests\StoreDoingRequest;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;

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
    public function store(StoreDoingRequest $request)
    { 
        $doing = Doing::create
            ($request->safe()->only('title', 'date') + [
                // ! somehow this is needed to make the ulid work, otherwise throws an error, trait doesn't work
                'id' => (string) Str::lower(Str::ulid()),
                'state' => \App\States\Doing\Draft::$name,
            ]);
        
        $doing->users()->sync(Auth::id());

        $taskDueDate = $request->safe()->only('date')['date'];

        $doingCreationTasks = [['name' => 'Išgryninti veiklos tikslą su koordinatoriumi', 'due_date' => $taskDueDate], 
            ['name' => 'Įkelti reikalingus dokumentus į failų skiltį', 'due_date' => $taskDueDate], 
            ['name' => 'Pateikti peržiūrai', 'due_date' => $taskDueDate]
        ];

        $doing->storeTasks($doingCreationTasks, $doing->users);

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

        // if soft deleted, redirect to index
        if ($doing->trashed()) {
            return redirect()->route('dashboard')->with('info', 'Veikla yra ištrinta! Ją atkurti gali administratorius.');
        }

        $modelName = Str::of(class_basename($doing))->camel()->plural();

        $doing->load('activities.causer', 'comments', 'users', 'files')->load(['tasks' => function ($query) {
            $query->with('users', 'taskable');
        }]);

        return Inertia::render('Admin/Representation/ShowDoing', [
            'doing' => [
                ...$doing->toArray(),
                'approvable' => $this->authorizer->forUser(auth()->user())->check($modelName . '.update.padalinys'),
                'sharepointPath' => $doing->users->first() ? SharepointFileService::pathForFileableDriveItem($doing) : null,
            ],
            'taskableInstitutions' => Inertia::lazy(fn () => $doing->institutions->load('users')),
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

        // update doing with model events, so without update()
        $doing->fill($request->only('title', 'date'))->save();

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

        // check if doing state is draft
        if (!($doing->state instanceof \App\States\Doing\Draft)) {
            return back()->with('info', 'Jau tvirtinama / tvirtinta veikla gali būti tik atšaukiama!');
        }

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

        return redirect()->route('dashboard')->with('success', 'Veikla ištrinta!');
    }
}
