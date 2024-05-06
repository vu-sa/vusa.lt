<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Navigation;
use App\Services\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NavigationController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Navigation::class, $this->authorizer]);

        return Inertia::render('Admin/Navigation/IndexNavigation', [
            'navigation' => NavigationService::getNavigationForPublic(),
            'typeOptions' => Inertia::lazy(fn () => MainPageController::getMainPageTypeOptions(request()->input('type'))),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', [Navigation::class, $this->authorizer]);

        // If root navigation or simple

        $parent_id = $request?->parent_id ?? 0;

        return Inertia::render('Admin/Navigation/CreateNavigation',
            [
                'parent_id' => $parent_id,
                'parentElements' => Navigation::where('parent_id', 0)->get(),
                'typeOptions' => Inertia::lazy(fn () => MainPageController::getMainPageTypeOptions(request()->input('type'))),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Navigation::class, $this->authorizer]);

        $navigation = new Navigation($request->all());

        $navigation->order = Navigation::where('parent_id', $navigation->parent_id)->max('order') + 1;

        $navigation->save();

        Cache::forget('mainNavigation-'.app()->getLocale());

        return redirect()->route('navigation.index')->with('success', 'Navigation created.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Navigation $navigation)
    {
        $this->authorize('update', [Navigation::class, $navigation, $this->authorizer]);

        return Inertia::render('Admin/Navigation/EditNavigation', [
            'navigationElement' => $navigation,
            'parentElements' => Navigation::where('parent_id', 0)->get(),
            'typeOptions' => Inertia::lazy(fn () => MainPageController::getMainPageTypeOptions(request()->input('type'))),
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

        $navigation->fill($request->all());

        $navigation->save();

        Cache::forget('mainNavigation-'.app()->getLocale());

        return back()->with('success', 'Navigation updated.');
    }

    public function updateColumn(Request $request)
    {
        $data = $request->all();

        $navigation = Navigation::find($data['id']);

        // direction - left or right

        $direction = $data['direction'];

        // lowest column nr - 1, max - 3

        $column = $navigation->extra_attributes['column'] ?? 1;

        if ($direction === 'left') {
            $column = $column - 1;
        } else {
            $column = $column + 1;
        }

        if ($column < 1) {
            $column = 1;
        }

        if ($column > 3) {
            $column = 3;
        }

        $navigation->extra_attributes = array_merge($navigation->extra_attributes, ['column' => $column]);

        $navigation->save();

        Cache::forget('mainNavigation-'.app()->getLocale());

        return back()->with('success', 'Navigation column updated.');
    }

    public function updateOrder(Request $request)
    {
        $data = $request->all();

        foreach ($data['navigation'] as $key => $value) {
            $navigation = Navigation::find($value['id']);
            $navigation->order = $key;
            $navigation->save();

            $children = isset($value['links']) ? $value['links'] : null;

            if ($children) {
                // Flatten array
                $children = collect($children)->flatten(1)->toArray();

                foreach ($children as $childKey => $child) {
                    $childNavigation = Navigation::find($child['id']);
                    $childNavigation->order = $childKey;
                    $childNavigation->save();
                }
            }
        }
        
        Cache::forget('mainNavigation-'.app()->getLocale());

        return back()->with('success', 'Navigation order updated.');
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

        Cache::forget('mainNavigation-'.app()->getLocale());

        return back()->with('success', 'Navigation deleted.');
    }
}
