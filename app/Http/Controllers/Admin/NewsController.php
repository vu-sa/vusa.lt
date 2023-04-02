<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\News;
use App\Models\Padalinys;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [News::class, $this->authorizer]);

        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $news = $indexer->execute(News::class, $search, 'title', $this->authorizer, null);

        return Inertia::render('Admin/Content/IndexNews', [
            'news' => $news->with('padalinys:id,shortname,alias')->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [News::class, $this->authorizer]);

        return Inertia::render('Admin/Content/CreateNews');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [News::class, $this->authorizer]);

        $request->validate([
            'title' => 'required',
            'permalink' => 'required',
            'text' => 'required',
            'lang' => 'required',
            'image' => 'required',
            'publish_time' => 'required',
        ]);

        $padalinys_id = null;

        // check if super admin, else set padalinys_id
        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $padalinys_id = Padalinys::where('type', 'pagrindinis')->first()->id;
        } else {
            $padalinys_id = $this->authorizer->permissableDuties->first()->padaliniai->first()->id;
        }

        News::create([
            'title' => $request->title,
            'permalink' => $request->permalink,
            'text' => $request->text,
            'short' => $request->short,
            'lang' => $request->lang,
            'other_lang_id' => $request->other_lang_id,
            'image' => $request->image,
            'image_author' => $request->image_author,
            'publish_time' => $request->publish_time,
            'draft' => $request->draft ?? 0,
            'padalinys_id' => $padalinys_id,
        ]);

        return redirect()->route('news.index')->with('success', 'Naujiena sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(News $news)
    {
        return $this->authorize('view', [
            News::class,
            $news,
            $this->authorizer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(News $news)
    {
        $this->authorize('update', [News::class, $news, $this->authorizer]);

        $other_lang_pages = News::with('padalinys:id,shortname')->when(! request()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) use ($news) {
            $query->where('padalinys_id', $news->padalinys_id);
        })->where('lang', '!=', $news->lang)->select('id', 'title', 'padalinys_id')->get();

        return Inertia::render('Admin/Content/EditNews', [
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'permalink' => $news->permalink,
                'text' => $news->text,
                'lang' => $news->lang,
                'other_lang_id' => $news->getOtherLanguage()?->id,
                'category' => $news->category,
                'padalinys' => $news->padalinys,
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
    public function update(Request $request, News $news)
    {
        $this->authorize('update', [News::class, $news, $this->authorizer]);

        $other_lang_page = News::find($news->other_lang_id);

        $news->update($request->only('title', 'text', 'lang', 'other_lang_id', 'draft', 'short', 'image', 'image_author', 'publish_time'));

        // update other lang id page
        if ($request->other_lang_id) {
            // overwrite other lang id page
            $other_lang_page = News::find($request->other_lang_id);
            $other_lang_page->other_lang_id = $news->id;
            $other_lang_page->save();
        } elseif (is_null($request->other_lang_id) && ! is_null($other_lang_page)) {
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();
        }

        return back()->with('success', 'Naujiena sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(News $news)
    {
        $this->authorize('delete', [News::class, $news, $this->authorizer]);

        $news->delete();

        return redirect()->route('news.index')->with('info', 'Naujiena sėkmingai ištrinta!');
    }

    // TODO: ....
    public function searchForNews(Request $request)
    {
        $data = $request->collect()['data'];

        $news = News::where('title', 'like', "%{$data['title']}%")->where('lang', $data['lang'])->get();

        $news = $news->map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'padalinys' => $news->padalinys,
            ];
        });

        return back()->with('search_news', $news);
    }
}
