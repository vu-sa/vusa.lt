<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PagesController extends ResourceController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Page::class, $this->authorizer]);

        $padaliniai = request()->input('padaliniai');
        $title = request()->input('title');

        $pages = Page::
            // check if admin, if not return only pages from current user padalinys
            when(!$request->user()->hasRole(config('permission.super_admin_role_name')), function ($query) use ($request) {
                $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
                // check request for padaliniai, if not empty return only pages from request padaliniai
            })->when(!empty($padaliniai), function ($query) use ($padaliniai) {
                $query->whereIn('padalinys_id', $padaliniai);
            })->when(!is_null($title), function ($query) use ($title) {
                $query->where('title', 'like', "%{$title}%");
            })->with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Content/IndexPages', [
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Page::class, $this->authorizer]);
        
        return Inertia::render('Admin/Content/CreatePage');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Page::class, $this->authorizer]);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'lang' => 'required|string',
            'permalink' => 'required|string|max:255|unique:pages',
        ]);

        $padalinys_id = User::find(Auth::id())->padalinys()?->id;

        if (is_null($padalinys_id)) {
            $padalinys_id = request()->user()->hasRole(config('permission.super_admin_role_name')) ? 16 : null;
        }

        $page = Page::create([
            'title' => $request->title,
            'permalink' => $request->permalink,
            'lang' => $request->lang,
            'text' => $request->text,
            'other_lang_id' => $request->other_lang_id,
            'padalinys_id' => $padalinys_id
        ]);

        return redirect()->route('pages.index')->with('success', 'Puslapis sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        $this->authorize('view', [Page::class, $page, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        $this->authorize('update', [Page::class, $page, $this->authorizer]);
        
        $other_lang_pages = Page::with('padalinys:id,shortname')->when(!request()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) use ($page) {
            $query->where('padalinys_id', request()->user()->padalinys()->id);  
        })->where('lang', '!=', $page->lang)->select('id', 'title', 'padalinys_id')->get();

        return Inertia::render('Admin/Content/EditPage', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'permalink' => $page->permalink,
                'text' => $page->text,
                'lang' => $page->lang,
                'other_lang_id' => $page->getOtherLanguage()?->only('id')['id'],
                'category' => $page->category,
                'padalinys' => $page->padalinys,
                'is_active' => $page->is_active,
                'aside' => $page->aside,
            ],
            'otherLangPages' => $other_lang_pages,
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
        $this->authorize('update', [Page::class, $page, $this->authorizer]);
        
        $other_lang_page = Page::find($page->other_lang_id);

        $page->update($request->only('title', 'text', 'lang', 'other_lang_id'));

        // update other lang id page
        if ($request->other_lang_id) {
            // overwrite other lang id page
            $other_lang_page = Page::find($request->other_lang_id);
            $other_lang_page->other_lang_id = $page->id;
            $other_lang_page->save();
        } else if (is_null($request->other_lang_id) && !is_null($other_lang_page)) {
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();
        }

        return back()->with('success', 'Puslapis atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Page $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        $this->authorize('delete', [Page::class, $page, $this->authorizer]);
        
        $page->delete();

        return redirect()->route('pages.index')->with('info', 'Puslapis ištrintas');
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
