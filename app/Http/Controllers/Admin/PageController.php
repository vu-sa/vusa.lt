<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Padalinys;
use App\Models\Page;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Page::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Page(), request(), $this->authorizer);

        $pages = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns(['created_at' => 'desc'])
            ->builder->paginate(20);

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

        $padalinys_id = null;

        // check if super admin, else set padalinys_id
        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $padalinys_id = Padalinys::where('type', 'pagrindinis')->first()->id;
        } else {
            $padalinys_id = $this->authorizer->permissableDuties->first()->padaliniai->first()->id;
        }

        Page::create([
            'title' => $request->title,
            'permalink' => $request->permalink,
            'lang' => $request->lang,
            'text' => $request->text,
            'other_lang_id' => $request->other_lang_id,
            'padalinys_id' => $padalinys_id,
        ]);

        return redirect()->route('pages.index')->with('success', 'Puslapis sėkmingai sukurtas!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        $this->authorize('update', [Page::class, $page, $this->authorizer]);

        $other_lang_pages = Page::with('padalinys:id,shortname')->when(! request()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) use ($page) {
            $query->where('padalinys_id', $page->padalinys_id);
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
        } elseif (is_null($request->other_lang_id) && ! is_null($other_lang_page)) {
            $other_lang_page->other_lang_id = null;
            $other_lang_page->save();
        }

        return back()->with('success', 'Puslapis atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        $this->authorize('delete', [Page::class, $page, $this->authorizer]);

        $page->delete();

        return redirect()->route('pages.index')->with('info', 'Puslapis ištrintas');
    }

    public function restore(Page $page, Request $request)
    {
        $this->authorize('restore', [Page::class, $page, $this->authorizer]);

        $page->restore();

        return back()->with('success', 'Puslapis sėkmingai atkurtas!');
    }
}
