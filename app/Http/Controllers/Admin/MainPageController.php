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
        $mainPage = MainPage::all();

        return Inertia::render('Admin/Content/MainPage/Index', [
            'mainPage' => $mainPage->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text,
                    'link' => $item->link,
                    'padalinys' => $item->padalinys,
                    'lang' => $item->lang,
                    'created_at' => $item->created_at,
                ];
            }),
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
        //
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
        //
    }
}
