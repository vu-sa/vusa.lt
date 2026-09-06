<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\ReorderNavigationRequest;
use App\Http\Requests\StoreNavigationRequest;
use App\Http\Requests\UpdateNavigationRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Models\Category;
use App\Models\Navigation;
use App\Services\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NavigationController extends AdminController
{
    use HandlesSoftDeletes;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Navigation::class);

        $showDeleted = $request->boolean('showDeleted', false);
        $lang = $this->resolveLang($request);

        $deletedCount = Navigation::onlyTrashed()
            ->where('lang', $lang)
            ->count();

        // The builder needs the live, uncached tree with `extra_attributes` intact and
        // every column present (even empty ones) — `getNavigationForPublic()` is cached,
        // flattens extra_attributes, and drops empty columns, all wrong for editing.
        $navigation = $showDeleted
            ? Navigation::onlyTrashed()
                ->where('lang', $lang)
                ->orderBy('parent_id')
                ->orderBy('order')
                ->get()
            : NavigationService::getTreeForAdmin($lang);

        return $this->inertiaResponse('Admin/Navigation/IndexNavigation', [
            'navigation' => $navigation,
            // The footer manager has its own, much simpler tree (no drag-and-drop, no
            // fixed 3-column shape) — see NavigationService::getFooterTreeForAdmin().
            // Not shown in trash view: trashed footer columns/links surface through the
            // same flat trashed list as everything else.
            'footerNavigation' => $showDeleted ? [] : NavigationService::getFooterTreeForAdmin($lang),
            'lang' => $lang,
            'showDeleted' => $showDeleted,
            'deletedCount' => $deletedCount,
            // Eager, not Inertia::optional() — the drift banner needs to render on the
            // very first load, and this is a single lightweight uncached query.
            'translationSummary' => $this->buildTranslationSummary(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->handleAuthorization('create', Navigation::class);

        $parent_id = $request->parent_id ?? 0;

        // A new root picks its location explicitly (the "add column" vs "add root"
        // entry point); a new child inherits it from the parent it's being created
        // under, same as it inherits the parent's language below.
        $location = (int) $parent_id === 0
            ? $this->resolveLocation($request->input('location'))
            : $this->resolveLocation(Navigation::withTrashed()->find($parent_id)?->extra_attributes['location'] ?? null);

        return $this->inertiaResponse('Admin/Navigation/CreateNavigation',
            [
                'parent_id' => $parent_id,
                'lang' => $this->resolveLang($request),
                'location' => $location,
                'parentElements' => $this->rootElementsForLocation($location),
                'categoryOptions' => $this->getCategoryOptions(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNavigationRequest $request)
    {
        $validated = $request->validated();

        $navigation = new Navigation($validated);

        // Trashed siblings still occupy their order, and a restored one would otherwise
        // come back sharing a position with whatever was created in the meantime.
        $navigation->order = Navigation::withTrashed()->where('parent_id', $navigation->parent_id)->max('order') + 1;

        if ($navigation->parent_id === 0) {
            // Roots pick their own language explicitly (the builder's lang switcher);
            // fall back to the request locale only when none was supplied.
            $navigation->lang = $validated['lang'] ?? app()->getLocale();
        } else {
            // Children always inherit the parent's language — the parent doesn't always
            // exist, so look through trashed ones too, falling back to the request
            // locale would create the child in the wrong language, where it silently
            // disappears from that language's menu.
            $navigation->lang = Navigation::withTrashed()->where('id', $navigation->parent_id)->first()->lang ?? app()->getLocale();
        }

        $navigation->save();

        return $this->redirectToIndexWithSuccess('navigation', $this->entityMessage('created', 'navigation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Navigation $navigation)
    {
        $this->handleAuthorization('update', $navigation);

        $location = $this->resolveLocation($navigation->extra_attributes['location'] ?? null);

        return $this->inertiaResponse('Admin/Navigation/EditNavigation', [
            'navigationElement' => $navigation,
            'parentElements' => $this->rootElementsForLocation($location)->where('lang', $navigation->lang)->values(),
            'categoryOptions' => $this->getCategoryOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNavigationRequest $request, Navigation $navigation)
    {
        $navigation->fill($request->validated());

        $navigation->save();

        return back()->with('success', $this->entityMessage('updated', 'navigation'));
    }

    /**
     * Persist drag-reordered navigation.
     *
     * `links` is an array of up to 3 column arrays (array position = column number,
     * 1-indexed), each holding that column's links top-to-bottom — exactly the shape
     * the builder already groups links into for rendering. Order is the index *within
     * its column array*, not a flattened position, so a column move can never corrupt
     * another column's relative order the way the old `flatten(1)` did.
     */
    public function updateOrder(ReorderNavigationRequest $request)
    {
        // Navigation is a globally-scoped model (see NavigationPolicy /
        // HasCommonChecks::commonChecker) — it has no tenant relation, so `update` is
        // granted only via the blanket `navigation.update.all` permission and does not
        // vary per row. One check up front is equivalent to the old per-row loop.
        $this->handleAuthorization('update', new Navigation);

        $data = $request->validated();

        foreach ($data['navigation'] as $rootOrder => $root) {
            // ->save() (not a query-builder update()) so the model's `saved` hook fires
            // and clears the public navigation cache.
            $rootNavigation = Navigation::findOrFail($root['id']);
            $rootNavigation->order = $rootOrder;
            $rootNavigation->save();

            foreach (($root['links'] ?? []) as $columnIndex => $columnItems) {
                $column = $columnIndex + 1;

                foreach ($columnItems as $childOrder => $child) {
                    $childNavigation = Navigation::findOrFail($child['id']);
                    $extraAttributes = $childNavigation->extra_attributes ?? [];
                    $extraAttributes['column'] = $column;

                    $childNavigation->order = $childOrder;
                    $childNavigation->extra_attributes = $extraAttributes;
                    $childNavigation->save();
                }
            }
        }

        return back()->with('success', __('messages.navigation.order_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Navigation $navigation)
    {
        $this->handleAuthorization('delete', $navigation);

        $navigation->delete();

        return redirect()->route('navigation.index')->with('info', $this->entityMessage('deleted', 'navigation'));
    }

    public function restore(Navigation $navigation): RedirectResponse
    {
        return $this->restoreModel($navigation);
    }

    public function forceDelete(Navigation $navigation): RedirectResponse
    {
        return $this->forceDeleteModel($navigation);
    }

    /**
     * Which language the builder is currently editing — independent of the admin UI's
     * own locale, so switching to the EN menu doesn't require switching the whole
     * admin interface to English.
     */
    private function resolveLang(Request $request): string
    {
        $lang = $request->input('lang');

        return in_array($lang, ['lt', 'en'], true) ? $lang : app()->getLocale();
    }

    /**
     * Which menu a create/edit form is working within — 'footer' only when explicitly
     * tagged, same fallback NavigationService uses for any pre-existing row.
     */
    private function resolveLocation(mixed $location): string
    {
        return $location === 'footer' ? 'footer' : 'header';
    }

    /**
     * Root elements (parent selector options / footer root's own siblings) scoped to
     * one menu, so a header form never offers a footer column as a parent and vice
     * versa — the two builders assume disjoint root sets (NavigationService).
     *
     * @return Collection<int, Navigation>
     */
    private function rootElementsForLocation(string $location): Collection
    {
        return Navigation::where('parent_id', 0)->get()
            ->filter(fn (Navigation $root) => $this->resolveLocation($root->extra_attributes['location'] ?? null) === $location)
            ->values();
    }

    /**
     * Categories aren't Typesense-searchable (7 rows repo-wide, not worth indexing —
     * see AGENTS.md), so the form's link-target picker falls back to a plain list here
     * instead of the multi-collection search dialog used for pages/news/etc.
     *
     * @return array<int, array{id: int, name: string, alias: string|null}>
     */
    private function getCategoryOptions(): array
    {
        return Category::query()->get(['id', 'name', 'alias'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'alias' => $category->alias,
            ])
            ->all();
    }

    /**
     * Heuristic, advisory drift indicator between the LT and EN trees: total item
     * counts per language, plus root items whose child count differs between the two
     * (matched by `order`, since roots have no other cross-language link). There is no
     * pairing table — a root that doesn't line up by order is simply not compared.
     *
     * @return array{counts: array<string, int>, mismatchedRoots: list<array<string, mixed>>}
     */
    private function buildTranslationSummary(): array
    {
        /** @var Collection<int, Navigation> $all */
        $all = Navigation::all(['id', 'lang', 'parent_id', 'order']);

        $counts = $all->countBy('lang');

        $childCounts = $all->where('parent_id', '!=', 0)->groupBy('parent_id')->map->count();

        $rootsByLangAndOrder = $all->where('parent_id', 0)
            ->groupBy('lang')
            ->map(fn (Collection $group) => $group->keyBy('order'));

        $ltRoots = $rootsByLangAndOrder->get('lt', collect());
        $enRoots = $rootsByLangAndOrder->get('en', collect());

        $mismatchedRoots = [];

        foreach ($ltRoots->keys()->merge($enRoots->keys())->unique() as $order) {
            $ltRoot = $ltRoots->get($order);
            $enRoot = $enRoots->get($order);

            $ltChildren = $ltRoot ? ($childCounts[$ltRoot->id] ?? 0) : null;
            $enChildren = $enRoot ? ($childCounts[$enRoot->id] ?? 0) : null;

            if ($ltChildren !== $enChildren) {
                $mismatchedRoots[] = [
                    'order' => $order,
                    'lt_children' => $ltChildren,
                    'en_children' => $enChildren,
                ];
            }
        }

        return [
            'counts' => [
                'lt' => $counts->get('lt', 0),
                'en' => $counts->get('en', 0),
            ],
            'mismatchedRoots' => $mismatchedRoots,
        ];
    }
}
