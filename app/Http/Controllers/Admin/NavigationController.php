<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Navigation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NavigationController extends LaravelResourceController
{
    public function __construct()
    {
        $this->authorizeResource(Navigation::class, 'navigation');
    }

    public function postNavigation($array, $id = 0)
    {
        for ($i = 0; $i < count($array); $i++) {
            $navigation = Navigation::find($array[$i]['key']);
            $navigation->name = $array[$i]['label'];
            $navigation->url = $array[$i]['url'];
            $navigation->parent_id = $id;
            $navigation->order = $i;
            $navigation->save();
            if (isset($array[$i]['children'])) {
                $this->postNavigation($array[$i]['children'], $navigation->id);
            }
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $_request  TODO: $request variable unused and possibly can be removed.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Navigation::class, $this->authorizer]);

        return Inertia::render('Admin/Navigation/Index', [
            'navigation' => Navigation::where('lang', '=', 'lt')->orderBy('order')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Navigation::class, $this->authorizer]);

        return Inertia::render('Admin/Navigation/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Navigation::class, $this->authorizer]);

        $this->postNavigation($request->_value);

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Navigation $navigation)
    {
        return $this->authorize('view', [
            Navigation::class,
            $navigation,
            $this->authorizer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Navigation $navigation)
    {
        $this->authorize('update', [Navigation::class, $navigation, $this->authorizer]);

        return Inertia::render('Admin/Navigation/Edit', [
            'navigation' => $navigation,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Navigation $navigation)
    {
        $this->authorize('update', [Navigation::class, $navigation, $this->authorizer]);

        $navigation->name = $request->name;
        $navigation->url = $request->url;
        // $navigation->order = $request->order;
        $navigation->save();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Navigation $navigation)
    {
        $this->authorize('delete', [Navigation::class, $navigation, $this->authorizer]);

        $navigation->delete();

        return back();
    }
}
