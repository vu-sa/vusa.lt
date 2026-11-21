<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Content\BulkDestroyPagesRequest;
use App\Http\Requests\Content\BulkUpdatePageStatusRequest;
use App\Http\Requests\IndexPageRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Page;
use App\Models\PublicUrl;
use App\Models\Tag;
use App\Services\ContentEditorService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\PostCreateRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PageController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', Page::class)]
    public function index(IndexPageRequest $request)
    {
        // Live rows come from Typesense (the scoped key carries the authorization) and the trash
        // from api.v1.admin.trash.index, so the page itself needs no rows.
        return Inertia::render('Admin/Content/IndexPages', [
            'deletedCount' => $this->scopedTrashedCount(Page::query(), 'tenant', 'pages.read.padalinys'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', Page::class)]
    public function create(Request $request)
    {
        return Inertia::render('Admin/Content/CreatePage',
            [
                'availableTags' => Tag::orderBy('alias')->get()->map->toFullArray(),
                'assignableTenants' => GetTenantsForUpserts::execute($request->user(), 'pages.create.padalinys', $this->authorizer),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', Page::class)]
    public function store(StorePageRequest $request, PostCreateRedirect $redirect)
    {
        $page = app(ContentEditorService::class)->save('pages', $request->validated(), $request->user());

        return redirect($redirect->url($request->user(), $page, 'pages.edit', 'update', 'pages.index'))
            ->with('success', $this->entityMessage('created', 'page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'page')]
    public function edit(Page $page)
    {
        $page->load('tenant:id,alias,shortname', 'parent:id,title');

        return Inertia::render('Admin/Content/EditPage', [
            'page' => [
                ...app(ContentEditorService::class)->snapshot($page),
                'public_urls' => $page->publicUrls()->get(['id', 'url', 'locale', 'created_at']),
                'parent' => $page->parent?->only('id', 'title'),
                'descendant_ids' => $page->descendantIds(),
            ],
            'availableTags' => Tag::orderBy('alias')->get()->map->toFullArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'page')]
    public function update(UpdatePageRequest $request, Page $page)
    {
        $page = app(ContentEditorService::class)->save('pages', $request->validated(), $request->user(), $page);

        return back()->with('success', $this->entityMessage('updated', 'page'))->with('data', $page->load('content'));
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'page')]
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('pages.index')->with('info', $this->entityMessage('deleted', 'page'));
    }

    /**
     * Publish or unpublish the pages picked in the collection (one id for the inline status menu).
     * Saved one by one so Scout, the public index and the activity log see every change.
     */
    // Authorized in BulkUpdatePageStatusRequest: every selected page.
    public function bulkUpdateStatus(BulkUpdatePageStatusRequest $request): RedirectResponse
    {
        $pages = $request->records();
        $isActive = $request->boolean('published');

        DB::transaction(fn () => $pages->each(fn (Page $page) => $page->update(['is_active' => $isActive])));

        return back()->with('success', __('messages.bulk_updated', ['count' => $pages->count()]));
    }

    // Authorized in BulkDestroyPagesRequest: every selected page.
    public function bulkDestroy(BulkDestroyPagesRequest $request): RedirectResponse
    {
        $pages = $request->records();

        DB::transaction(fn () => $pages->each(fn (Page $page) => $page->delete()));

        return back()->with('info', __('messages.bulk_deleted', ['count' => $pages->count()]));
    }

    #[Authorize('restore', 'page')]
    public function restore(Page $page): RedirectResponse
    {
        return $this->restoreModel($page, $this->entityMessage('restored', 'page'));
    }

    #[Authorize('forceDelete', 'page')]
    public function forceDelete(Page $page): RedirectResponse
    {
        return $this->forceDeleteModel($page);
    }

    /**
     * Remove a legacy public URL (an old permalink that still 301-redirects here).
     */
    #[Authorize('update', 'page')]
    public function destroyPublicUrl(Page $page, PublicUrl $publicUrl): RedirectResponse
    {
        $publicUrl->delete();

        return back()->with('info', $this->entityMessage('deleted', 'publicUrl'));
    }
}
