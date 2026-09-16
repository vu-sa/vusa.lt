<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Calendar;
use App\Models\News;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class TopicController extends PublicController
{
    /**
     * A topic aggregates published news, active pages and upcoming/recent events sharing one
     * tag — across every tenant. Only tags flagged `is_topic` get a page; the rest of the
     * vocabulary stays filter/search-only (see Tag::scopeTopics()).
     */
    public function show(string $lang, string $topicString, Tag $tag)
    {
        abort_unless($tag->is_topic, 404);

        $this->getBanners();
        $this->getTenantLinks();

        $this->shareOtherLangURL('topic', null, null, ['tag' => $tag->alias]);

        $locale = app()->getLocale();

        $news = News::query()
            ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
            ->where('lang', $locale)
            ->where('draft', false)
            ->where('publish_time', '<=', now())
            ->with('tenant:id,alias,shortname')
            ->orderByDesc('publish_time')
            ->limit(6)
            ->get(['id', 'title', 'short', 'image', 'permalink', 'lang', 'tenant_id', 'publish_time']);

        $pages = Page::query()
            ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
            ->where('lang', $locale)
            ->where('is_active', true)
            ->with('tenant:id,alias,shortname')
            ->orderByDesc('publish_time')
            ->limit(10)
            ->get(['id', 'title', 'permalink', 'lang', 'tenant_id']);

        $events = Calendar::query()
            ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
            ->published()
            ->forLocale($locale)
            ->where('date', '>=', Carbon::now()->startOfDay())
            ->with('tenant:id,alias,shortname')
            ->orderBy('date')
            ->limit(6)
            ->get();

        $this->applyPageHead(
            contentTenant: $this->tenant,
            title: $tag->name,
            description: $tag->description,
        );

        return Inertia::render('Public/TopicPage', [
            'topic' => $tag->only('id', 'name', 'description', 'alias'),
            'news' => $news->map(fn (News $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'lang' => $article->lang,
                'short' => $article->short,
                'image' => $article->getImageUrl(),
                'permalink' => $article->permalink,
                'publish_time' => $article->publish_time,
                'public_url' => $article->publicUrl(),
            ]),
            'pages' => $pages->map(fn (Page $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'tenant_name' => $page->tenant->shortname,
                'public_url' => $page->publicUrl(),
            ]),
            'events' => $events->map(fn (Calendar $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->date,
                'tenant_name' => $event->tenant->shortname,
                'public_url' => $event->publicUrl($locale),
            ]),
        ]);
    }
}
