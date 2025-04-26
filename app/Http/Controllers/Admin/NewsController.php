<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DuplicateNewsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNewsRequest;
use App\Models\Content;
use App\Models\News;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', News::class);

        $indexer = new ModelIndexer(new News);

        $news = $indexer
            ->setEloquentQuery([fn ($query) => $query->with('other_language_news:id,title,lang')])
            ->filterAllColumns()
            ->sortAllColumns(['publish_time' => 'descend'])
            ->builder->paginate(20);

        return Inertia::render('Admin/Content/IndexNews', [
            'news' => $news,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', News::class);

        return Inertia::render('Admin/Content/CreateNews');
    }

    public function duplicate(News $news)
    {
        $this->authorize('create', News::class);

        $newNews = (new DuplicateNewsAction)->execute($news);

        return redirect()->route('news.edit', $newNews)->with('success', 'Naujiena sėkmingai nukopijuota!');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', News::class);

        $request->validate([
            'title' => 'required',
            'permalink' => 'required|unique:news,permalink',
            'content.parts' => 'required',
            'lang' => 'required',
            'image' => 'required',
            'publish_time' => 'required',
            'short' => 'required',
        ]);

        $tenant_id = null;

        // check if super admin, else set tenant_id
        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $tenant_id = Tenant::where('type', 'pagrindinis')->first()->id;
        } else {
            $tenant_id = $this->authorizer->permissableDuties->first()->tenants->first()->id;
        }

        $content = new Content;

        $content->save();

        $content->parts()->createMany($request->content['parts']);

        $news = News::create([
            'title' => $request->title,
            'permalink' => $request->permalink,
            'short' => $request->short,
            'lang' => $request->lang,
            'content_id' => $content->id,
            'other_lang_id' => $request->other_lang_id,
            'image' => $request->image,
            'image_author' => $request->image_author,
            'draft' => $request->draft ?? 0,
            'tenant_id' => $tenant_id,
        ]);

        if (is_string($request->publish_time)) {
            $news->publish_time = strtotime($request->publish_time);
        } else {
            $news->publish_time = $request->publish_time / 1000;
        }

        $news->save();

        return redirect()->route('news.index')->with('success', 'Naujiena sėkmingai sukurta!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(News $news)
    {
        $this->authorize('update', $news);

        $other_lang_pages = News::with('tenant:id,shortname')->when(! request()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) use ($news) {
            $query->where('tenant_id', $news->tenant_id);
        })->where('lang', '!=', $news->lang)->select('id', 'title', 'tenant_id')->get();

        return Inertia::render('Admin/Content/EditNews', [
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'permalink' => $news->permalink,
                'content' => $news->content,
                'lang' => $news->lang,
                'other_lang_id' => $news->other_language_news?->id,
                'category' => $news->category,
                'tenant' => $news->tenant,
                'draft' => $news->draft,
                'short' => $news->short,
                'image' => $news->image,
                'tags' => $news->tags,
                'image_author' => $news->image_author,
                'publish_time' => $news->publish_time,
            ],
            'otherLangNews' => $other_lang_pages,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateNewsRequest $request, News $news)
    {
        $other_lang_page = News::find($news->other_lang_id);

        $news->update($request->only(
            'title',
            'lang',
            'other_lang_id',
            'draft',
            'publish_time',
            'short',
            'image',
            'image_author',
        ));

        $news->save();

        $content = Content::query()->find($news->content->id);

        app(\App\Services\ContentService::class)->updateContentParts($content, $request->content['parts']);

        // update other lang id page
        if ($request->other_lang_id) {

            // find page that has other lang id
            $current_other_lang_page = News::where('other_lang_id', $news->id)->first();

            if ($current_other_lang_page) {
                $current_other_lang_page->other_lang_id = null;
                $current_other_lang_page->save();
            }

            // overwrite other lang id page
            $other_lang_page = News::find($request->other_lang_id);
            $other_lang_page->other_lang_id = $news->id;
            $other_lang_page->save();
        } elseif (is_null($request->other_lang_id) && ! is_null($other_lang_page)) {
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();
        }

        return back()->with('success', 'Naujiena sėkmingai atnaujinta!')->with('data', $news->load('content'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(News $news)
    {
        $this->authorize('delete', $news);

        $news->delete();

        return redirect()->route('news.index')->with('info', 'Naujiena sėkmingai ištrinta!');
    }

    /**
     * Restore the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(News $news)
    {
        $this->authorize('restore', $news);

        $news->restore();

        return back()->with('success', 'Naujiena sėkmingai atkurta!');
    }
}
