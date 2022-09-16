<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class BannerController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Banner::class, 'banner');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $title = request()->input('title');
        $padaliniai = request()->input('padaliniai');

        $banners = Banner::
            // check if admin, if not return only pages from current user padalinys
            when(!$request->user()->hasRole('Super Admin'), function ($query) use ($request) {
                $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
                // check request for padaliniai, if not empty return only pages from request padaliniai
            })->when(!empty($padaliniai), function ($query) use ($padaliniai) {
                $query->whereIn('padalinys_id', $padaliniai);
            })->when(!is_null($title), function ($query) use ($title) {
                $query->where('title', 'like', "%{$title}%");
            })->with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Content/IndexBanners', [
            'banners' => $banners
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Content/CreateBanner');
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
            'image_url' => 'required',
        ]);
        
        $banner = new Banner();
        // $banner->text = $request->text;
        $banner->title = $request->title;
        $banner->is_active = $request->is_active;
        $banner->link_url = $request->link_url ?? "";
        // add random banner order for now
        $banner->order = rand(1, 10);
        $banner->image_url = $request->image_url;
        $banner->padalinys_id = $request->user()->padalinys()->id;
        $banner->user_id = $request->user()->id;
        $banner->save();

        return redirect()->route('banners.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        return Inertia::render('Admin/Content/EditBanner', [
            'banner' => $banner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Banner $banner)
    {
        $banner->title = $request->title;
        $banner->is_active = $request->is_active;
        $banner->link_url = $request->link_url ?? "";
        $banner->image_url = $request->image_url;
        $banner->save();

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Banner $banner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();

        return redirect()->route('banners.index');
    }
}
