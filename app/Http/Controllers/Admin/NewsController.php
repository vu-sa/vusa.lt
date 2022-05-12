<?php

namespace App\Http\Controllers\Admin;

use App\Models\News;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

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
        $news = News::with(['padalinys' => function ($query) {
            $query->select('id', 'shortname', 'alias');
        }])->orderByDesc('created_at')->paginate(10);

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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
}
