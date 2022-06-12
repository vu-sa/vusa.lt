<?php

namespace App\Http\Controllers\Admin;

use App\Models\News;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(News::class, 'news');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $padaliniai = $request->padaliniai;
        $title = $request->title;

        $news = News::
            // check if admin, if not return only pages from current user padalinys
            when(!$request->user()->isAdmin(), function ($query) use ($request) {
                $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
                // check request for padaliniai, if not empty return only pages from request padaliniai
            })->when(!empty($padaliniai), function ($query) use ($padaliniai) {
                $query->whereIn('padalinys_id', $padaliniai);
            })->when(!is_null($title), function ($query) use ($title) {
                $query->where('title', 'like', "%{$title}%");
            })->with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Content/News/Index', [
            'news' => $news
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Content/News/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd(Auth::user(), $request);

        $request->validate([
            'title' => 'required',
            'permalink' => 'required',
            'text' => 'required',
            'lang' => 'required',
            'image' => 'required',
            'publish_time' => 'required',
        ]);

        // dd()

        News::create([
            'title' => $request->title,
            'permalink' => $request->permalink,
            'text' => $request->text,
            'short' => $request->short,
            'lang' => $request->lang,
            'other_lang_id' => $request->other_lang_news,
            'image' => $request->image,
            'image_author' => $request->image_author,
            'publish_time' => $request->publish_time,
            'draft' => $request->draft ?? 0,
            'padalinys_id' => User::find(Auth::user()->id)->padalinys()->id,
        ]);

        return redirect()->route('news.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\News  $news
     * @return \Illuminate\Http\Response
     */
    public function show(News $news)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\News  $news
     * @return \Illuminate\Http\Response
     */
    public function edit(News $news)
    {
        return Inertia::render('Admin/Content/News/Edit', [
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'permalink' => $news->permalink,
                'text' => $news->text,
                'lang' => $news->lang,
                'other_lang_page' => $news->getOtherLanguage()?->id,
                'category' => $news->category,
                'padalinys' => $news->padalinys,
                'draft' => $news->draft,
                'short' => $news->short,
                'image' => $news->image,
                'tags' => $news->tags,
                'image_author' => $news->image_author,
                'publish_time' => $news->publish_time,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\News  $news
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, News $news)
    {
        $news->update($request->only('title', 'text', 'lang', 'draft', 'short', 'image', 'image_author', 'publish_time'));

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\News $news
     * @return \Illuminate\Http\Response
     */
    public function destroy(News $news)
    {
        //
    }

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
