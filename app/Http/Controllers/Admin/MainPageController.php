<?php

namespace App\Http\Controllers\Admin;

use App\Models\MainPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\DB;

class MainPageController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [MainPage::class, $this->authorizer]);
        
        $padaliniai = request()->input('padaliniai');
        $text = request()->input('text');

        $mainPages = MainPage::
            // check if admin, if not return only pages from current user padalinys
            when(!$request->user()->hasRole(config('permission.super_admin_role_name')), function ($query) use ($request) {
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
        $this->authorize('create', [MainPage::class, $this->authorizer]);
        
        return Inertia::render('Admin/Content/CreateMainPage');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [MainPage::class, $this->authorizer]);
        
        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            
            $mainPage = new MainPage;
            
            $mainPage->text = $request->text;
            $mainPage->link = $request->link;
            $mainPage->lang = $request->lang;
            $mainPage->position = '';
            $mainPage->padalinys()->associate($request->user()->padalinys());
            $mainPage->save();
        });

        return redirect()->route('mainPages.index')->with('success', 'Sėkmingai pridėtas pradinio puslapio mygtukas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function show(MainPage $mainPage)
    {
        $this->authorize('view', [MainPage::class, $mainPage, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function edit(MainPage $mainPage)
    {        
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);
        
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
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);
        
        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);
        
        DB::transaction(function () use ($request, $mainPage) {
            $mainPage->update($request->only('text', 'link', 'lang'));
        });

        return back()->with('success', 'Sėkmingai atnaujintas pradinio puslapio mygtukas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MainPage $mainPage
     * @return \Illuminate\Http\Response
     */
    public function destroy(MainPage $mainPage)
    {
        $this->authorize('delete', [MainPage::class, $mainPage, $this->authorizer]);
        
        $mainPage->delete();

        return redirect()->route('mainPage.index')->with('info', 'Sėkmingai ištrintas pradinio puslapio mygtukas!');
    }
}
