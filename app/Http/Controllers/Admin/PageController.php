<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PairTranslatedRecord;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexPageRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Category;
use App\Models\Content;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ContentService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;

class PageController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexPageRequest $request)
    {
        $this->handleAuthorization('viewAny', Page::class);

        $query = Page::query()->with('tenant:id,shortname');

        $searchableColumns = ['title', 'permalink'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'pages.read.padalinys',
            ]
        );

        $deletedCount = $this->getTrashedCount($query);

        $pages = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/Content/IndexPages', [
            'pages' => [
                'data' => $pages->items(),
                'meta' => [
                    'total' => $pages->total(),
                    'per_page' => $pages->perPage(),
                    'current_page' => $pages->currentPage(),
                    'last_page' => $pages->lastPage(),
                    'from' => $pages->firstItem(),
                    'to' => $pages->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->boolean('showDeleted', false),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Page::class);

        return $this->inertiaResponse('Admin/Content/CreatePage',
            [
                'categories' => Category::all(['id', 'name']),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request)
    {
        $this->handleAuthorization('create', Page::class);

        $tenant_id = null;

        // check if super admin, else set tenant_id
        if (request()->user()->isSuperAdmin()) {
            $tenant_id = Tenant::where('type', 'pagrindinis')->first()?->id;
        } else {
            $tenant_id = $this->authorizer->permissableDuties->first()?->tenants->first()?->id;
        }

        $content = new Content;

        $content->save();

        $page = Page::query()->create([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'content_id' => $content->id,
            'permalink' => $request->permalink,
            'lang' => $request->lang,
            'is_active' => $request->is_active,
            'layout' => $request->layout ?? 'default',
            'show_table_of_contents' => $request->boolean('show_table_of_contents', true),
            'show_title' => $request->boolean('show_title', true),
            'show_breadcrumbs' => $request->boolean('show_breadcrumbs', true),
            'tenant_id' => $tenant_id,
        ]);

        // Created after the Page so the parts' first activity-log entries can
        // already resolve their root up to the Page (see App\Support\ActivityRoots)
        // instead of self-rooting to the not-yet-owned Content.
        $content->parts()->createMany($request->content['parts']);

        // Pairing goes through the action rather than the create payload: it has to
        // release whoever already holds the counterpart id, trashed rows included.
        PairTranslatedRecord::execute($page, $request->other_lang_id);

        return redirect()->route('pages.index')->with('success', 'Puslapis sėkmingai sukurtas!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        $this->handleAuthorization('update', $page);

        $page->load('tenant:id,alias,shortname');

        $other_lang_pages = Page::with('tenant:id,shortname')->when(! request()->user()->isSuperAdmin(), function ($query) use ($page): void {
            $query->where('tenant_id', $page->tenant_id);
        })->where('lang', '!=', $page->lang)->select('id', 'title', 'tenant_id')->get();

        return $this->inertiaResponse('Admin/Content/EditPage', [
            'page' => [
                ...$page->only('id', 'title', 'content', 'permalink', 'text', 'lang', 'category_id', 'tenant_id', 'is_active', 'aside', 'layout', 'show_table_of_contents', 'show_title', 'show_breadcrumbs'),
                'tenant' => $page->tenant->only('id', 'alias', 'shortname'),
                'other_lang_id' => $page->getOtherLanguage()?->only('id')['id'] ?? null,
            ],
            'otherLangPages' => $other_lang_pages,
            'categories' => Category::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $this->handleAuthorization('update', $page);

        $page->update([
            ...$request->only('title', 'lang', 'category_id', 'is_active', 'layout', 'permalink'),
            'show_table_of_contents' => $request->boolean('show_table_of_contents', true),
            'show_title' => $request->boolean('show_title', true),
            'show_breadcrumbs' => $request->boolean('show_breadcrumbs', true),
        ]);

        $content = Content::query()->find($page->content->id);

        // Use ContentService to efficiently update content parts
        app(ContentService::class)->updateContentParts($content, $request->content['parts']);

        PairTranslatedRecord::execute($page, $request->other_lang_id);

        return back()->with('success', 'Puslapis atnaujintas!')->with('data', $page->load('content'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $this->handleAuthorization('delete', $page);

        $page->delete();

        return redirect()->route('pages.index')->with('info', 'Puslapis ištrintas');
    }

    public function restore(Page $page): RedirectResponse
    {
        return $this->restoreModel($page, 'Puslapis sėkmingai atkurtas!');
    }

    public function forceDelete(Page $page): RedirectResponse
    {
        return $this->forceDeleteModel($page);
    }
}
