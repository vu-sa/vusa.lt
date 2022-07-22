<?php

namespace App\Http\Controllers\Admin;

use App\Models\MainPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class MainPageController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(MainPage::class, 'mainPage');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $padaliniai = request()->input('padaliniai');
        $text = request()->input('text');

        $mainPages = MainPage::
            // check if admin, if not return only pages from current user padalinys
            when(!$request->user()->isAdmin(), function ($query) use ($request) {
                $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
                // check request for padaliniai, if not empty return only pages from request padaliniai
            })->when(!empty($padaliniai), function ($query) use ($padaliniai) {
                $query->whereIn('padalinys_id', $padaliniai);
            })->when(!is_null($text), function ($query) use ($text) {
                $query->where('text', 'like', "%{$text}%");
            })->with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Content/IndexMainPages', [
            'mainPages' => $mainPages,
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function show(MainPage $mainPage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function edit(MainPage $mainPage)
    {
        return Inertia::render('Admin/Content/EditMainPage', [
            'mainPage' => $mainPage
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MainPage $mainPage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MainPage $mainPage
     * @return \Illuminate\Http\Response
     */
    public function destroy(MainPage $mainPage)
    {
        $mainPage->delete();

        return redirect()->route('mainPage.index');
    }
}
