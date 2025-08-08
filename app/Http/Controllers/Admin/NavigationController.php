<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Navigation;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NavigationController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Navigation::class);

        return $this->inertiaResponse('Admin/Navigation/IndexNavigation', [
            'navigation' => NavigationService::getNavigationForPublic(),
            'typeOptions' => Inertia::lazy(fn () => QuickLinkController::getQuickLinkTypeOptions($request->input('type'))),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->handleAuthorization('create', Navigation::class);

        // If root navigation or simple

        $parent_id = $request->parent_id ?? 0;

        return $this->inertiaResponse('Admin/Navigation/CreateNavigation',
            [
                'parent_id' => $parent_id,
                'parentElements' => Navigation::where('parent_id', 0)->get(),
                'typeOptions' => Inertia::lazy(fn () => QuickLinkController::getQuickLinkTypeOptions(request()->input('type'))),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->handleAuthorization('create', Navigation::class);

        $navigation = new Navigation($request->all());

        $navigation->order = Navigation::where('parent_id', $navigation->parent_id)->max('order') + 1;

        // The parent navigation element doesn't always exist
        $navigation->lang = Navigation::where('id', $navigation->parent_id)->first()->lang ?? app()->getLocale();

        $navigation->save();

        Cache::forget('mainNavigation-'.app()->getLocale());

        return $this->redirectToIndexWithSuccess('navigation', 'Navigation created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Navigation $navigation)
    {
        $this->handleAuthorization('update', $navigation);

        return $this->inertiaResponse('Admin/Navigation/EditNavigation', [
            'navigationElement' => $navigation,
            'parentElements' => Navigation::where('parent_id', 0)->get(),
            'typeOptions' => Inertia::lazy(fn () => QuickLinkController::getQuickLinkTypeOptions(request()->input('type'))),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Navigation $navigation)
    {
        $this->handleAuthorization('update', $navigation);

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
     */
    public function destroy(Navigation $navigation)
    {
        $this->handleAuthorization('delete', $navigation);

        $navigation->delete();

        Cache::forget('mainNavigation-'.app()->getLocale());

        return redirect()->route('navigation.index')->with('info', 'Navigation deleted.');
    }
}
