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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PageController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexPageRequest $request)
    {
        $this->handleAuthorization('viewAny', Page::class);

        // Live rows come from Typesense (the scoped key carries the authorization) and the trash
        // from api.v1.admin.trash.index, so the page itself needs no rows.
        return $this->inertiaResponse('Admin/Content/IndexPages', [
            'deletedCount' => $this->scopedTrashedCount(Page::query(), 'tenant', 'pages.read.padalinys'),
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
                'availableTags' => Tag::orderBy('alias')->get()->map->toFullArray(),
                'assignableTenants' => GetTenantsForUpserts::execute('pages.create.padalinys', $this->authorizer),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request)
    {
        $this->handleAuthorization('create', Page::class);

        app(ContentEditorService::class)->save('pages', $request->validated(), $request->user());

        return redirect()->route('pages.index')->with('success', $this->entityMessage('created', 'page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        $this->handleAuthorization('update', $page);

        $page->load('tenant:id,alias,shortname', 'parent:id,title');

        return $this->inertiaResponse('Admin/Content/EditPage', [
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
    public function update(UpdatePageRequest $request, Page $page)
    {
        $this->handleAuthorization('update', $page);

        $page = app(ContentEditorService::class)->save('pages', $request->validated(), $request->user(), $page);

        return back()->with('success', $this->entityMessage('updated', 'page'))->with('data', $page->load('content'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $this->handleAuthorization('delete', $page);

        $page->delete();

        return redirect()->route('pages.index')->with('info', $this->entityMessage('deleted', 'page'));
    }

    /**
     * Publish or unpublish the pages picked in the collection (one id for the inline status menu).
     * Saved one by one so Scout, the public index and the activity log see every change.
     */
    public function bulkUpdateStatus(BulkUpdatePageStatusRequest $request): RedirectResponse
    {
        $pages = $request->records();
        $isActive = $request->boolean('published');

        DB::transaction(fn () => $pages->each(fn (Page $page) => $page->update(['is_active' => $isActive])));

        return back()->with('success', __('messages.bulk_updated', ['count' => $pages->count()]));
    }

    public function bulkDestroy(BulkDestroyPagesRequest $request): RedirectResponse
    {
        $pages = $request->records();

        DB::transaction(fn () => $pages->each(fn (Page $page) => $page->delete()));

        return back()->with('info', __('messages.bulk_deleted', ['count' => $pages->count()]));
    }

    public function restore(Page $page): RedirectResponse
    {
        return $this->restoreModel($page, $this->entityMessage('restored', 'page'));
    }

    public function forceDelete(Page $page): RedirectResponse
    {
        return $this->forceDeleteModel($page);
    }

    /**
     * Remove a legacy public URL (an old permalink that still 301-redirects here).
     */
    public function destroyPublicUrl(Page $page, PublicUrl $publicUrl): RedirectResponse
    {
        $this->handleAuthorization('update', $page);

        // Resolve through the relation so a crafted payload cannot reach another page's URLs.
        $publicUrlFromDb = $page->publicUrls()->find($publicUrl->id);

        abort_if($publicUrlFromDb === null, 403, 'Public URL does not belong to this page.');

        $publicUrlFromDb->delete();

        return back()->with('info', $this->entityMessage('deleted', 'publicUrl'));
    }
}
