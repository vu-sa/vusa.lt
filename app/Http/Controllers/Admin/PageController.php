<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Category;
use App\Models\Content;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;

class PageController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Page::class);

        $indexer = new ModelIndexer(new Page);

        $pages = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns(['created_at' => 'desc'])
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/Content/IndexPages', [
            'pages' => $pages,
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

        $content->parts()->createMany($request->content['parts']);

        Page::query()->create([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'content_id' => $content->id,
            'permalink' => $request->permalink,
            'lang' => $request->lang,
            'other_lang_id' => $request->other_lang_id,
            'is_active' => $request->is_active,
            'layout' => $request->layout ?? 'default',
            'tenant_id' => $tenant_id,
        ]);

        return redirect()->route('pages.index')->with('success', 'Puslapis sėkmingai sukurtas!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        $this->handleAuthorization('update', $page);

        $page->load('tenant:id,alias,shortname');

        $other_lang_pages = Page::with('tenant:id,shortname')->when(! request()->user()->isSuperAdmin(), function ($query) use ($page) {
            $query->where('tenant_id', $page->tenant_id);
        })->where('lang', '!=', $page->lang)->select('id', 'title', 'tenant_id')->get();

        return $this->inertiaResponse('Admin/Content/EditPage', [
            'page' => [
                ...$page->only('id', 'title', 'content', 'permalink', 'text', 'lang', 'category_id', 'tenant_id', 'is_active', 'aside', 'layout'),
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

        $other_lang_page = Page::find($page->other_lang_id);

        $page->update($request->only('title', 'lang', 'other_lang_id', 'category_id', 'is_active', 'layout'));

        $content = Content::query()->find($page->content->id);

        // Use ContentService to efficiently update content parts
        app(\App\Services\ContentService::class)->updateContentParts($content, $request->content['parts']);

        // update other lang id page
        if ($request->other_lang_id) {
            // overwrite other lang id page
            $other_lang_page = Page::find($request->other_lang_id);
            $other_lang_page->other_lang_id = $page->id;
            $other_lang_page->save();
        } elseif (is_null($request->other_lang_id) && ! is_null($other_lang_page)) {
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();
        }

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

    public function restore(Page $page, Request $request)
    {
        $this->handleAuthorization('restore', $page);

        $page->restore();

        return back()->with('success', 'Puslapis sėkmingai atkurtas!');
    }
}
