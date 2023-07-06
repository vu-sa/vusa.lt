<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetPadaliniaiForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResourceController extends LaravelResourceController
{
    public function __construct()
    {
        parent::__construct();

        // TODO: precognition may not work well with file uploads, also when array is made of simple object and file upload
        // $this->middleware([HandlePrecognitiveRequests::class])->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Resource::class, $this->authorizer]);

        $resources = Resource::search(request()->input('text'));

        // ! filter, sort by other where

        // ...

        // ! filter by padalinys

        $resources = ModelIndexer::filterByAuthorized($resources, $this->authorizer, false);
        // copy by value
        // $resources_1 = clone $resources;
        // $resources_1 = $resources_1->query(fn (Builder $query) => $query->with('padalinys', 'reservations', 'media'));
        // dd($resources, $resources_1->get());

        return Inertia::render('Admin/Reservations/IndexResource', [
            'resources' => $resources->get()->load('media')->paginate(15)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Resource::class, $this->authorizer]);

        return Inertia::render('Admin/Reservations/CreateResource', [
            'assignablePadaliniai' => GetPadaliniaiForUpserts::execute('resources.create.all', $this->authorizer)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request)
    {
        $this->authorize('create', [Resource::class, $this->authorizer]);

        $resource = new Resource();

        $resource->fill($request->safe()->except('media'));
        $resource->save();

        $resource = $resource->fresh();

        foreach ($request->validated('media') as $image) {
            $resource->addMedia($image['file'])->toMediaCollection('images');
        }

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
        $this->authorize('update', [Resource::class, $resource, $this->authorizer]);

        return Inertia::render('Admin/Reservations/EditResource', [
            'resource' => $resource->toFullArray()
                + ['left_capacity' => $resource->leftCapacity()]
                + ['media' => $resource->getMedia('images')->map(fn ($image) => [
                        'id' => $image->id,
                        'name' => $image->name,
                        'type' => $image->mime_type,
                        'status' => 'finished',
                        'url' => $image->getUrl(),
                    ])
                ],
            'assignablePadaliniai' => GetPadaliniaiForUpserts::execute('resources.update.all', $this->authorizer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $this->authorize('update', [Resource::class, $resource, $this->authorizer]);

        $resource->fill($request->safe()->except('media'));
        $resource->save();

        // first, intersect existing media with id from request, delete one's that didn't match
        $resource->getMedia('images')
            ->filter(fn ($image) => !in_array($image->id, array_column($request->validated('media'), 'id')))
            ->each(fn ($image) => $image->delete());

        // then add new media
        foreach ($request->validated('media') as $image) {
            if ($image['status'] === 'pending' && $image['file']) {
                $resource->addMedia($image['file'])->toMediaCollection('images');
            }
        }

        return back()->with('success', 'Sėkmingai atnaujintas išteklius.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $this->authorize('delete', [Resource::class, $resource, $this->authorizer]);

        $resource->delete();

        return redirect()->route('resources.index')->with('success', 'Sėkmingai ištrintas išteklius.');
    }
}
