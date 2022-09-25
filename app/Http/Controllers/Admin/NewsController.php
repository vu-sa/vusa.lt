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
            when(!$request->user()->hasRole('Super Admin'), function ($query) use ($request) {
                $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
                // check request for padaliniai, if not empty return only pages from request padaliniai
            })->when(!empty($padaliniai), function ($query) use ($padaliniai) {
                $query->whereIn('padalinys_id', $padaliniai);
            })->when(!is_null($title), function ($query) use ($title) {
                $query->where('title', 'like', "%{$title}%");
            })->with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Content/IndexNews', [
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
        return Inertia::render('Admin/Content/CreateNews');
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

        $padalinys_id = User::find(Auth::user()->id)->padalinys()?->id;

        if (is_null($padalinys_id)) {
            $padalinys_id = request()->user()->hasRole('Super Admin') ? 16 : null;
        }

        $news = News::create([
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
            'padalinys_id' => $padalinys_id
        ]);

        return redirect()->route('news.index')->with('success', 'Naujiena sėkmingai sukurta!');
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
        $other_lang_pages = News::with('padalinys:id,shortname')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
            $query->where('padalinys_id', request()->user()->padalinys()->id);  
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\News  $news
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, News $news)
    {
        $other_lang_page = News::find($news->other_lang_id);
        
        $news->update($request->only('title', 'text', 'lang', 'other_lang_id', 'draft', 'short', 'image', 'image_author', 'publish_time'));

        // update other lang id page
        if ($request->other_lang_id) {
            // overwrite other lang id page
            $other_lang_page = News::find($request->other_lang_id);
            $other_lang_page->other_lang_id = $news->id;
            $other_lang_page->save();
        } else if (is_null($request->other_lang_id) && !is_null($other_lang_page)) {
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();
        }

        return back()->with('success', 'Naujiena sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\News $news
     * @return \Illuminate\Http\Response
     */
    public function destroy(News $news)
    {
        $news->delete();

        return redirect()->route('news.index')->with('info', 'Naujiena sėkmingai ištrinta!');
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
