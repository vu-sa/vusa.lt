<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTagRequest;
use App\Http\Requests\MergeTagsRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\News;
use App\Models\Tag;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', Tag::class)]
    public function index(IndexTagRequest $request): Response
    {
        // Build base query
        $query = Tag::query();

        // Define searchable columns
        $searchableColumns = ['name', 'description', 'alias'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        // Paginate results
        $deletedCount = $this->getTrashedCount($query);

        $tags = $query->paginate($request->getPerPage())
            ->withQueryString();

        // Get the sorting state
        $sorting = $request->getSorting();

        return Inertia::render('Admin/Content/IndexTag', [
            'abilities' => [
                'update' => $request->user()->can('update', new Tag),
                'delete' => $request->user()->can('delete', new Tag),
            ],
            'tags' => [
                'data' => $tags->getCollection()
                    ->map(function ($tag) {
                        /** @var Tag $tag */
                        return $tag->toFullArray();
                    }),
                'meta' => [
                    'total' => $tags->total(),
                    'per_page' => $tags->perPage(),
                    'current_page' => $tags->currentPage(),
                    'last_page' => $tags->lastPage(),
                    'from' => $tags->firstItem(),
                    'to' => $tags->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
            'initialSorting' => $sorting,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', Tag::class)]
    public function create(): Response
    {
        return Inertia::render('Admin/Content/CreateTag');
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', Tag::class)]
    public function store(StoreTagRequest $request): RedirectResponse
    {
        $tag = Tag::create($request->validated());

        return ($request->header('X-Tag-Sheet') === 'true'
            ? to_route('tags.index')
            : to_route('tags.edit', $tag))
            ->with('success', __('Tag created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'tag')]
    public function edit(Tag $tag): Response
    {
        // Get news that use this tag
        $news = $tag->news()
            ->select('news.id', 'title', 'permalink', 'publish_time', 'lang', 'tenant_id')
            ->with('tenant:id,shortname')
            ->orderBy('publish_time', 'desc')
            ->get()
            ->map(function ($newsItem) {
                /** @var News $newsItem */
                return [
                    'id' => $newsItem->id,
                    'title' => $newsItem->title,
                    'permalink' => $newsItem->permalink,
                    'publish_time' => $newsItem->publish_time,
                    'lang' => $newsItem->lang,
                    'tenant' => $newsItem->tenant->shortname,
                ];
            });

        return Inertia::render('Admin/Content/EditTag', [
            'postTag' => $tag->toFullArray(),
            'news' => $news,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'tag')]
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return back()->with('success', __('Tag updated successfully'));
    }

    /**
     * Merge multiple tags into a target tag.
     */
    // Authorized in MergeTagsRequest: the target and source tags in the body.
    public function processMergeTags(MergeTagsRequest $request)
    {
        $targetTagId = $request->validated('target_tag_id');
        $sourceTagIds = $request->validated('source_tag_ids');

        if (in_array($targetTagId, $sourceTagIds)) {
            return back()->withErrors(['source_tag_ids' => 'Target tag cannot be in the list of tags to merge.']);
        }

        $targetTag = Tag::findOrFail($targetTagId);

        DB::transaction(function () use ($targetTag, $sourceTagIds): void {
            $sourceTags = Tag::whereIn('id', $sourceTagIds)->get();

            foreach ($sourceTags as $sourceTag) {
                foreach (Tag::TAGGABLE_RELATIONS as $relation) {
                    $sourceIds = $sourceTag->{$relation}()->pluck($sourceTag->{$relation}()->getRelated()->getTable().'.id');

                    $targetTag->{$relation}()->syncWithoutDetaching($sourceIds);

                    $sourceTag->{$relation}()->detach();
                }
            }

            Tag::whereIn('id', $sourceTagIds)->delete();
        });

        return to_route('tags.index')
            ->with('success', __('Tags merged successfully. :count tags merged into :target.', [
                'count' => count($sourceTagIds),
                'target' => is_array($targetTag->name) ? ($targetTag->name['lt'] ?? $targetTag->name['en'] ?? 'Unknown') : $targetTag->name,
            ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'tag')]
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return to_route('tags.index')
            ->with('success', __('Tag deleted successfully'));
    }

    #[Authorize('restore', 'tag')]
    public function restore(Tag $tag): RedirectResponse
    {
        return $this->restoreModel($tag);
    }

    #[Authorize('forceDelete', 'tag')]
    public function forceDelete(Tag $tag): RedirectResponse
    {
        return $this->forceDeleteModel($tag);
    }
}
