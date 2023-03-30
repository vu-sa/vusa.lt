<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\MainPage;
use App\Models\Padalinys;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $mainPages = $indexer->execute(MainPage::class, $search, 'text', $this->authorizer, false);

        return Inertia::render('Admin/Content/IndexMainPages', [
            'mainPages' => $mainPages->paginate(20),
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [MainPage::class, $this->authorizer]);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $padalinys_id = Padalinys::where('type', 'pagrindinis')->first()->id;
        } else {
            $padalinys_id = $this->authorizer->permissableDuties->first()->padaliniai->first()->id;
        }

        DB::transaction(function () use ($request, $padalinys_id) {
            $mainPage = new MainPage;

            $mainPage->text = $request->text;
            $mainPage->link = $request->link;
            $mainPage->lang = $request->lang;
            $mainPage->position = '';
            $mainPage->padalinys()->associate($padalinys_id);
            $mainPage->save();
        });

        return redirect()->route('mainPage.index')->with('success', 'Sėkmingai pridėtas pradinio puslapio mygtukas!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(MainPage $mainPage)
    {
        return $this->authorize('view', [
            MainPage::class,
            $mainPage,
            $this->authorizer
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(MainPage $mainPage)
    {
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);

        return Inertia::render('Admin/Content/EditMainPage', [
            'mainPage' => $mainPage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(MainPage $mainPage)
    {
        $this->authorize('delete', [MainPage::class, $mainPage, $this->authorizer]);

        $mainPage->delete();

        return redirect()->route('mainPage.index')->with('info', 'Sėkmingai ištrintas pradinio puslapio mygtukas!');
    }
}
