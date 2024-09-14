<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoingRequest;
use App\Models\Doing;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DoingController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Doing::class);

        $indexer = new ModelIndexer(new Doing, request(), $this->authorizer);

        $doings = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return Inertia::render('Admin/Representation/IndexDoing', [
            'doings' => $doings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Doing::class);

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
        $doing = new Doing;

        // fill doing instead of create
        $doing->fill($request->safe()->only('title', 'date') + [
            // ! somehow this is needed to make the ulid work, otherwise throws an error, trait doesn't work
            'id' => (string) Str::lower(Str::ulid()),
            'state' => \App\States\Doing\Draft::$name,
        ])->save();

        // check if doing has type, then find the type, and sync id to doing
        if ($request->safe()->has('type')) {
            $type = \App\Models\Type::where('slug', $request->safe()->only('type')['type'])->firstOrFail();
            $doing->types()->sync($type->id);
        }

        $doing->users()->sync(Auth::id());

        $taskDueDate = $request->safe()->only('date')['date'];

        $doingCreationTasks = [['name' => 'Išgryninti veiklos tikslą su koordinatoriumi', 'due_date' => $taskDueDate],
            ['name' => 'Įkelti reikalingus dokumentus į failų skiltį', 'due_date' => $taskDueDate],
            ['name' => 'Pateikti peržiūrai', 'due_date' => $taskDueDate],
        ];

        $doing->storeTasks($doingCreationTasks, $doing->users);

        return redirect()->route('doings.show', $doing)->with('success', 'Veiksmas sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Doing $doing)
    {
        $this->authorize('view', $doing);

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
                'approvable' => $this->authorizer->forUser(auth()->user())->check($modelName.'.update.padalinys'),
                'sharepointPath' => $doing->users->first() ? SharepointFileService::pathForFileableDriveItem($doing) : null,
            ],
            'taskableInstitutions' => Inertia::lazy(fn () => $doing->institutions->load('users')),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Doing $doing)
    {
        $this->authorize('update', $doing);

        return Inertia::render('Admin/Representation/EditDoing', [
            'doing' => $doing->load('types', 'users'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Doing $doing)
    {
        $this->authorize('update', $doing);

        $request->validate([
            'title' => 'required',
            'user_id' => 'required',
            'date' => 'required',
        ]);

        // update doing with model events, so without update()
        $doing->fill($request->only('title', 'date'))->save();

        $doing->users()->sync($request->only('user_id'));

        return redirect()->route('doings.show', $doing)->with('success', 'Veikla atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Doing $doing)
    {
        $this->authorize('delete', $doing);

        // check if doing state is draft
        if (! ($doing->state instanceof \App\States\Doing\Draft)) {
            return back()->with('info', 'Jau tvirtinama / tvirtinta veikla gali būti tik atšaukiama!');
        }

        DB::transaction(function () use ($doing) {
            // * Since the model is soft-deleted, doesn't make complete sense
            // * to detach relations.

            // detach doings from matters
            // $doing->matters()->detach();

            // detach doings from types
            // $doing->types()->detach();

            // delete tasks
            // $doing->tasks()->delete();

            // delete comments
            // $doing->comments()->delete();

            // delete doing
            $doing->delete();
        });

        return redirect()->route('dashboard')->with('success', 'Veikla ištrinta!');
    }

    /**
     * Restore the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(Doing $doing)
    {
        $this->authorize('restore', $doing);

        $doing->restore();

        return back()->with('success', 'Veikla atkurta!');
    }
}
