<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;

class ResourceController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Resource::class);

        $indexer = new ModelIndexer(new Resource);

        $resources = $indexer
            ->setEloquentQuery([fn (Builder $query) => $query->with(['media', 'category'])], false)
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/Reservations/IndexResource', [
            'resources' => $resources,
            'categories' => ResourceCategory::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Resource::class);

        return $this->inertiaResponse('Admin/Reservations/CreateResource', [
            'assignableTenants' => GetTenantsForUpserts::execute('resources.create.padalinys', $this->authorizer),
            'categories' => ResourceCategory::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request)
    {
        $this->handleAuthorization('create', Resource::class);

        $resource = new Resource;

        $resource->fill($request->safe()->except('media'));
        $resource->save();

        $resource = $resource->fresh();

        foreach ($request->validated('media') as $image) {
            $resource->addMedia($image['file'])->toMediaCollection('images');
        }

        return redirect()->route('resources.index')->with('success', trans_choice('messages.created', 1, ['model' => trans_choice('entities.resource.model', 1)]));
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
        $this->handleAuthorization('update', $resource);

        return $this->inertiaResponse('Admin/Reservations/EditResource', [
            'resource' => $resource->load('reservations.users')->toFullArray()
                + ['media' => $resource->getMedia('images')->map(fn ($image) => [
                    'id' => $image->id,
                    'name' => $image->name,
                    'type' => $image->mime_type,
                    'status' => 'finished',
                    'url' => $image->getUrl(),
                ]),
                ],
            'assignableTenants' => GetTenantsForUpserts::execute('resources.update.padalinys', $this->authorizer),
            'categories' => ResourceCategory::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource)
    {
        $resource->fill($request->safe()->except('media'));
        $resource->save();

        // first, intersect existing media with id from request, delete one's that didn't match
        $resource->getMedia('images')
            ->filter(fn ($image) => ! in_array($image->id, array_column($request->validated('media'), 'id')))
            ->each(fn ($image) => $image->delete());

        // then add new media
        if ($request->validated('media')) {
            foreach ($request->validated('media') as $image) {
                if ($image['status'] === 'pending' && $image['file']) {
                    $resource->addMedia($image['file'])->toMediaCollection('images');
                }
            }
        }

        return back()
            ->with('success', trans_choice('messages.updated', 1, ['model' => trans_choice('entities.resource.model', 1)]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $this->handleAuthorization('delete', $resource);

        $resource->delete();

        return redirect()->route('resources.index')
            ->with('info', trans_choice('messages.deleted', 1, ['model' => trans_choice('entities.resource.model', 1)]));
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Resource $resource)
    {
        $this->handleAuthorization('restore', $resource);

        $resource->restore();

        return back()->with('success', 'Išteklius sėkmingai atkurtas!');
    }
}
