<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Models\Content;
use App\Models\News;
use App\Models\Padalinys;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [News::class, $this->authorizer]);

        $indexer = new ModelIndexer(new News(), $request, $this->authorizer);

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
            'contents' => 'required',
            'lang' => 'required',
            'image' => 'required',
            'publish_time' => 'required',
            'short' => 'required',
        ]);

        $padalinys_id = null;

        // check if super admin, else set padalinys_id
        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $padalinys_id = Padalinys::where('type', 'pagrindinis')->first()->id;
        } else {
            $padalinys_id = $this->authorizer->permissableDuties->first()->padaliniai->first()->id;
        }

        $news = News::create([
            'title' => $request->title,
            'permalink' => $request->permalink,
            'short' => $request->short,
            'lang' => $request->lang,
            'other_lang_id' => $request->other_lang_id,
            'image' => $request->image,
            'image_author' => $request->image_author,
            'publish_time' => $request->publish_time,
            'draft' => $request->draft ?? 0,
            'padalinys_id' => $padalinys_id,
        ]);

        $news->contents()->createMany($request->contents);

        return redirect()->route('news.index')->with('success', 'Naujiena sėkmingai sukurta!');
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
                'contents' => $news->contents,
                'lang' => $news->lang,
                'other_lang_id' => $news->other_language_news?->id,
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

        $news->update($request->only('title', 'lang', 'other_lang_id', 'draft', 'short', 'image', 'image_author', 'publish_time'));

        $news->contents()->detach();

        foreach ($request->contents as $key => $content) {
            $id = $content['id'] ?? null;
            $model = Content::findOrNew($id);

            $model->type = $content['type'];
            $model->json_content = $content['json_content'];
            $model->options = $content['options'] ?? null;

            $model->save();

            // TODO: maybe there's need to delete contents, when none are attached to page or news
            $news->contents()->attach($model, ['order' => $key]);

            $model->save();
        }

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

        return back()->with('success', 'Naujiena sėkmingai atnaujinta!')->with('data', $news->load('contents'));
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

    /**
     * Restore the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(News $news)
    {
        $this->authorize('restore', [News::class, $news, $this->authorizer]);

        $news->restore();

        return back()->with('success', 'Naujiena sėkmingai atkurta!');
    }
}
