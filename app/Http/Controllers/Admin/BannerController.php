<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\LaravelResourceController;
use App\Models\Banner;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class BannerController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Banner::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Banner(), request(), $this->authorizer);

        $banners = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return Inertia::render('Admin/Content/IndexBanner', [
            'banners' => $banners,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Banner::class, $this->authorizer]);

        return Inertia::render('Admin/Content/CreateBanner');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Banner::class, $this->authorizer]);

        $request->validate([
            'title' => 'required',
            'image_url' => 'required',
        ]);

        $tenants = GetTenantsForUpserts::execute('banners.create.all', $this->authorizer);

        $banner = new Banner();
        // $banner->text = $request->text;
        $banner->title = $request->title;
        $banner->is_active = $request->is_active ?? 0;
        $banner->link_url = $request->link_url ?? '';
        // add random banner order for now
        $banner->order = rand(1, 10);
        $banner->image_url = $request->image_url;
        $banner->tenant_id = $tenants->first()['id'] ?? null;
        $banner->save();

        Cache::forget('banners-'.$banner->tenant_id);

        return redirect()->route('banners.index')->with('success', 'Baneris sėkmingai sukurtas!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        $this->authorize('update', [Banner::class, $banner, $this->authorizer]);

        return Inertia::render('Admin/Content/EditBanner', [
            'banner' => $banner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Banner $banner)
    {
        $this->authorize('update', [Banner::class, $banner, $this->authorizer]);

        $banner->title = $request->title;
        $banner->is_active = $request->is_active;
        $banner->link_url = $request->link_url ?? '';
        $banner->image_url = $request->image_url;
        $banner->save();

        Cache::forget('banners-'.$banner?->tenant_id);

        return back()->with('success', 'Baneris atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        $this->authorize('delete', [Banner::class, $banner, $this->authorizer]);

        $banner->delete();

        return redirect()->route('banners.index')->with('info', 'Baneris ištrintas!');
    }
}
