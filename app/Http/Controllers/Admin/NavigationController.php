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
     * @return \Illuminate\Http\Response
     */
    public function editAll()
    {
        $this->authorize('create', [Navigation::class, $this->authorizer]);

        return Inertia::render('Admin/Navigation/NavigationEdit', [
            'navigation' => Navigation::where('lang', '=', 'lt')->orderBy('order')->get(),
            'typeOptions' => Inertia::lazy(fn () => MainPageController::getMainPageTypeOptions(request()->input('type'))),
        ]);
    }

    public function updateAll(Request $request)
    {
        $data = $request->all();

        $flattenedData = [];

        foreach ($data['navigation'] as $key => $value) {
            $flattenedData[] = $value;

            $children = isset($value['children']) ? $value['children'] : null;

            if ($children) {
                foreach ($children as $key => $child) {
                    $child['order'] = $key;
                    $child['extra_attributes'] = [
                        'description' => $child['description'] ?? null,
                        'image' => $child['image'] ?? null,
                        'type' => $child['type'] ?? null,
                        'icon' => $child['icon'] ?? null,
                        'column' => $child['column'] ?? null,
                    ];
                    $flattenedData[] = $child;
                }
            }

        }

        foreach ($flattenedData as $key => $value) {
            $navigation = Navigation::updateOrCreate(['id' => $value['id']],
                [
                    'parent_id' => $value['parent_id'],
                    'name' => $value['name'],
                    'lang' => $value['lang'],
                    'url' => $value['url'],
                    'order' => $value['order'],
                    'is_active' => $value['is_active'],
                    'extra_attributes' => $value['extra_attributes'],
                ]);
        }

        return back();
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
        /*$navigation->save();*/

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
