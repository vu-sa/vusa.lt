<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTagRequest;
use App\Http\Requests\MergeTagsRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Tag;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Inertia\Inertia;

class TagController extends Controller
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTagRequest $request)
    {
        $this->authorize('viewAny', Tag::class);

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
        $tags = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        // Get the sorting state
        $sorting = $request->getSorting();

        return Inertia::render('Admin/Content/IndexTag', [
            'tags' => [
                'data' => $tags->getCollection()->map->toFullArray(),
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
            'initialSorting' => $sorting,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Tag::class);

        return Inertia::render('Admin/Content/CreateTag');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTagRequest $request)
    {
        $this->authorize('create', Tag::class);

        $tag = Tag::create($request->validated());

        return to_route('tags.edit', $tag)
            ->with('success', __('Tag created successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        $this->authorize('view', $tag);

        return Inertia::render('Admin/Content/ShowTag', [
            'tag' => $tag->toFullArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        $this->authorize('update', $tag);

        // Get news that use this tag
        $news = $tag->news()
            ->select('news.id', 'title', 'permalink', 'publish_time', 'lang', 'tenant_id')
            ->with('tenant:id,shortname')
            ->orderBy('publish_time', 'desc')
            ->get();

        return Inertia::render('Admin/Content/EditTag', [
            'postTag' => $tag->toFullArray(),
            'news' => $news->map(function ($newsItem) {
                return [
                    'id' => $newsItem->id,
                    'title' => $newsItem->title,
                    'permalink' => $newsItem->permalink,
                    'publish_time' => $newsItem->publish_time,
                    'lang' => $newsItem->lang,
                    'tenant' => $newsItem->tenant?->shortname,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $this->authorize('update', $tag);

        $tag->update($request->validated());

        return back()->with('success', __('Tag updated successfully'));
    }

    /**
     * Show the form for merging tags.
     */
    public function mergeTags()
    {
        $this->authorize('create', Tag::class);

        $tags = Tag::orderBy('alias')->get();

        return Inertia::render('Admin/Content/MergeTags', [
            'tags' => $tags->map->toFullArray(),
        ]);
    }

    /**
     * Merge multiple tags into a target tag.
     */
    public function processMergeTags(MergeTagsRequest $request)
    {
        $this->authorize('create', Tag::class);

        $targetTagId = $request->validated('target_tag_id');
        $sourceTagIds = $request->validated('source_tag_ids');

        // Ensure target tag is not in source tags
        if (in_array($targetTagId, $sourceTagIds)) {
            return back()->withErrors(['source_tag_ids' => 'Target tag cannot be in the list of tags to merge.']);
        }

        $targetTag = Tag::findOrFail($targetTagId);
        $sourceTags = Tag::whereIn('id', $sourceTagIds)->get();

        // Move all relationships from source tags to target tag
        foreach ($sourceTags as $sourceTag) {
            // Move news relationships
            $sourceTag->news()->each(function ($news) use ($targetTag) {
                // Only attach if not already attached to avoid duplicates
                if (! $targetTag->news()->where('news.id', $news->id)->exists()) {
                    $targetTag->news()->attach($news->id);
                }
            });

            // Detach relationships from source tag
            $sourceTag->news()->detach();
        }

        // Delete source tags
        Tag::whereIn('id', $sourceTagIds)->delete();

        return to_route('tags.index')
            ->with('success', __('Tags merged successfully. :count tags merged into :target.', [
                'count' => count($sourceTagIds),
                'target' => is_array($targetTag->name) ? ($targetTag->name['lt'] ?? $targetTag->name['en'] ?? 'Unknown') : $targetTag->name,
            ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return to_route('tags.index')
            ->with('success', __('Tag deleted successfully'));
    }
}
