<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;

class PagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $pages = Page::orderByDesc('created_at')->paginate(20);

        $pages = Page::with(['padalinys' => function ($query) {
            $query->select('id', 'shortname', 'alias');
        }])->orderByDesc('created_at')->paginate(20);

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

        return Inertia::render('Admin/Content/Pages/Edit', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'permalink' => $page->permalink,
                'text' => $page->text,
                'lang' => $page->lang,
                'other_lang_id' => $page->other_lang_id,
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
        //
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
