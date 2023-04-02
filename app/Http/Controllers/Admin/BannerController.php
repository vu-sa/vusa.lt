<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\Banner;
use App\Models\Padalinys;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BannerController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Banner::class, $this->authorizer]);

        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $banners = $indexer->execute(Banner::class, $search, 'title', $this->authorizer, false);

        return Inertia::render('Admin/Content/IndexBanners', [
            'banners' => $banners->paginate(20),
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

        $banner = new Banner();
        // $banner->text = $request->text;
        $banner->title = $request->title;
        $banner->is_active = $request->is_active ?? 0;
        $banner->link_url = $request->link_url ?? '';
        // add random banner order for now
        $banner->order = rand(1, 10);
        $banner->image_url = $request->image_url;
        $banner->padalinys_id = $request->user()->padalinys()?->id ?? Padalinys::where('alias', 'vusa')->first()->id;
        $banner->user_id = $request->user()->id;
        $banner->save();

        return redirect()->route('banners.index')->with('success', 'Baneris sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        return $this->authorize('view', [
            Banner::class,
            $banner,
            $this->authorizer
        ]);
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
