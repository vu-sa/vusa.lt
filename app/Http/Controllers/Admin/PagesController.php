<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class PagesController extends Controller
{
    
    public function __construct()
    {
        $this->authorizeResource(Page::class, 'page');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $pages = Page::orderByDesc('created_at')->paginate(20);

        // check if admin

        if ($request->user()->isAdmin()) {

        $pages = Page::with(['padalinys' => function ($query) {
            $query->select('id', 'shortname', 'alias');
        }])->orderByDesc('created_at')->paginate(20);

        } else {

            $pages = Page::with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->where('padalinys_id', '=', $request->user()->padalinys()->id)->orderByDesc('created_at')->paginate(20);

        
    }
    return Inertia::render('Admin/Content/Pages/Index', [
        'pages' => $pages
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
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        
        // if ($page->other_lang_id) {
        //     $other_page = $page->getOtherLanguage()->only('id', 'title');
        // } else {
        //     // return object with id and title null
        //     // $other_page = (object) ['id' => null, 'title' => null];
        // }

        return Inertia::render('Admin/Content/Pages/Edit', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'permalink' => $page->permalink,
                'text' => $page->text,
                'lang' => $page->lang,
                'other_lang_page' => $page->getOtherLanguage()?->id,
                'category' => $page->category,
                'padalinys' => $page->padalinys,
                'is_active' => $page->is_active,
                'aside' => $page->aside,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {
        // dd($request->only('title', 'text', 'lang', 'other_lang_id', 'category', 'is_active', 'aside'));
        
        $page->update($request->only('title', 'text', 'lang', 'other_lang_id'));

        // update other lang id page
        if ($request->has('other_lang_id')) {
            $other_lang_page = Page::find($request->other_lang_id);
            $other_lang_page->other_lang_id = $page->id;
            $other_lang_page->save();
        } else if (!is_null(Page::find($page->other_lang_id))) {
            $other_lang_page = Page::find($page->other_lang_id);
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();

            $page->other_lang_id = null;
            $page->save();
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Page $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        //
    }

    public function searchForPage(Request $request)

    {
        $data = $request->collect()['data'];

        $pages = Page::where('title', 'like', "%{$data['title']}%")->where('lang', $data['lang'])->get();

        $pages = $pages->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'padalinys' => $page->padalinys,
            ];
        });

        return back()->with('search_pages', $pages);
    }
}
