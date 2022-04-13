<?php

namespace App\Http\Controllers\Admin;

use App\Models\Navigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class NavigationController extends Controller
{

    public function getNavigation($id, $lang)
    {
        $childrenNav = Navigation::where('parent_id', $id)->where('lang', '=', $lang)->get()->sortBy('order');
        // if ($childrenNav->first() == null) {
        //     return;
        // }

        // $navigation = [];

        // foreach ($childrenNav as $child) {
        //     $navigation[] = [
        //         'key' => $child->id,
        //         'label' => $child->name,
        //         'url' => $child->url,
        //         'children' => $this->getNavigation($child->id, $lang),
        //     ];
        // }

        // return $navigation;
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
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('Admin/Navigation/Index', [
            'navigationLT' => Navigation::where('lang', '=', 'lt')->get(),
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
        $this->postNavigation($request->_value);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Http\Response
     */
    public function show(Navigation $navigation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Http\Response
     */
    public function edit(Navigation $navigation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Navigation  $navigation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Navigation $navigation)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Navigation $navigation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Navigation $navigation)
    {
        //
    }
}
