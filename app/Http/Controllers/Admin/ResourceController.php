<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetPadaliniaiForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Inertia\Inertia;

class ResourceController extends LaravelResourceController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware([HandlePrecognitiveRequests::class])->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Resource::class, $this->authorizer]);

        $search = request()->input('text');

        $resources = $this->indexer->execute(Resource::class, $search, 'name', $this->authorizer, false);

        return Inertia::render('Admin/Reservations/IndexResource', [
            'resources' => $resources->paginate(20),
            'padaliniai' => GetPadaliniaiForUpserts::execute('resources.create.all', $this->authorizer),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Resource::class, $this->authorizer]);

        return Inertia::render('Admin/Reservations/CreateResource');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request)
    {
        $resource = new Resource();

        $resource->fill($request->validated());
        $resource->save();

        return redirect()->route('resources.index')->with('success', 'Sėkmingai sukurtas išteklius.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource)
    {
        $this->authorize('update', [Resource::class, $this->authorizer]);

        return Inertia::render('Admin/Reservations/EditResource', [
            'resource' => $resource->toFullArray(),
            'padaliniai' => GetPadaliniaiForUpserts::execute('resources.update.all', $this->authorizer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $resource->fill($request->validated());
        $resource->save();

        return back()->with('success', 'Sėkmingai atnaujintas išteklius.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $this->authorize('delete', [Resource::class, $this->authorizer]);

        $resource->delete();

        return redirect()->route('resources.index')->with('success', 'Sėkmingai ištrintas išteklius.');
    }
}
