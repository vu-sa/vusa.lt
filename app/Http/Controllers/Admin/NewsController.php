<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DuplicateNewsAction;
use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Content\BulkDestroyNewsRequest;
use App\Http\Requests\Content\BulkUpdateNewsStatusRequest;
use App\Http\Requests\IndexNewsRequest;
use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\News;
use App\Models\PublicUrl;
use App\Models\Tag;
use App\Services\ContentEditorService;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class NewsController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexNewsRequest $request)
    {
        $this->handleAuthorization('viewAny', News::class);

        // Live rows come from Typesense (the scoped key carries the authorization) and the trash
        // from api.v1.admin.trash.index, so the page itself needs no rows.
        return $this->inertiaResponse('Admin/Content/IndexNews', [
            'deletedCount' => $this->scopedTrashedCount(News::query(), 'tenant', 'news.read.padalinys'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', News::class);

        $tags = Tag::orderBy('alias')->get();

        return $this->inertiaResponse('Admin/Content/CreateNews', [
            'availableTags' => $tags->map->toFullArray(),
            'assignableTenants' => GetTenantsForUpserts::execute('news.create.padalinys', $this->authorizer),
        ]);
    }

    public function duplicate(News $news)
    {
        $this->handleAuthorization('create', News::class);

        $newNews = DuplicateNewsAction::execute($news);

        return $this->redirectWithSuccess('news.edit', __('messages.news.duplicated'), $newNews);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNewsRequest $request)
    {
        app(ContentEditorService::class)->save('news', $request->validated(), $request->user());

        return $this->redirectToIndexWithSuccess('news', $this->entityMessage('created', 'news'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(News $news)
    {
        $this->handleAuthorization('update', $news);

        $tags = Tag::orderBy('alias')->get();

        return $this->inertiaResponse('Admin/Content/EditNews', [
            'news' => [
                ...app(ContentEditorService::class)->snapshot($news),
                'public_urls' => $news->publicUrls()->get(['id', 'url', 'locale', 'created_at']),
            ],
            'availableTags' => $tags->map->toFullArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNewsRequest $request, News $news)
    {
        $news = app(ContentEditorService::class)->save('news', $request->validated(), $request->user(), $news);

        return back()->with('success', $this->entityMessage('updated', 'news'))->with('data', $news->load('content'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        $this->handleAuthorization('delete', $news);

        $news->delete();

        return $this->redirectToIndexWithInfo('news', $this->entityMessage('deleted', 'news'));
    }

    /**
     * Restore the specified resource from storage.
     */
    /**
     * Publish or unpublish the articles picked in the collection (one id for the inline status menu).
     * Saved one by one so Scout, the public index and the activity log see every change.
     */
    public function bulkUpdateStatus(BulkUpdateNewsStatusRequest $request): RedirectResponse
    {
        $news = $request->records();
        $isDraft = ! $request->boolean('published');

        DB::transaction(fn () => $news->each(fn (News $article) => $article->update(['draft' => $isDraft])));

        return back()->with('success', __('messages.bulk_updated', ['count' => $news->count()]));
    }

    public function bulkDestroy(BulkDestroyNewsRequest $request): RedirectResponse
    {
        $news = $request->records();

        DB::transaction(fn () => $news->each(fn (News $article) => $article->delete()));

        return back()->with('info', __('messages.bulk_deleted', ['count' => $news->count()]));
    }

    public function restore(News $news): RedirectResponse
    {
        return $this->restoreModel($news, $this->entityMessage('restored', 'news'));
    }

    public function forceDelete(News $news): RedirectResponse
    {
        return $this->forceDeleteModel($news);
    }

    /**
     * Remove a legacy public URL (an old permalink that still 301-redirects here).
     */
    public function destroyPublicUrl(News $news, PublicUrl $publicUrl): RedirectResponse
    {
        $this->handleAuthorization('update', $news);

        // Resolve through the relation so a crafted payload cannot reach another article's URLs.
        $publicUrlFromDb = $news->publicUrls()->find($publicUrl->id);

        abort_if($publicUrlFromDb === null, 403, 'Public URL does not belong to this news article.');

        $publicUrlFromDb->delete();

        return back()->with('info', $this->entityMessage('deleted', 'publicUrl'));
    }
}
