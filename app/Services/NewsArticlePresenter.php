<?php

namespace App\Services;

use App\Models\Content;
use App\Models\News;
use App\Support\LocalizedRouteSlugs;

class NewsArticlePresenter
{
    /** @return array{id: int, title: string, short: string, lang: string, other_lang_id: int|null, permalink: string|null, publish_time: string|null, content: Content, important: int, main_points: string|null, read_more: string|null, show_breadcrumbs: bool, highlights: array<array-key, mixed>|null, image_author: string|null, tags: list<array{id: int, name: string, alias: string}>, reading_time: int, image: string|null, image_media: array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}|null, tenant: string} */
    public static function article(News $news, ?string $imageUrl): array
    {
        $tags = [];
        foreach ($news->tags as $tag) {
            $tags[] = [
                'id' => $tag->id,
                'name' => $tag->name,
                'alias' => $tag->alias,
            ];
        }

        $imageMedia = $news->imageData('image');

        return [
            'id' => $news->id,
            'title' => $news->title,
            'short' => $news->short,
            'lang' => $news->lang,
            'other_lang_id' => $news->other_lang_id,
            'permalink' => $news->permalink,
            'publish_time' => $news->publish_time?->toJSON(),
            'content' => $news->content,
            'important' => $news->important,
            'main_points' => $news->main_points,
            'read_more' => $news->read_more,
            'show_breadcrumbs' => $news->show_breadcrumbs,
            'highlights' => $news->highlights,
            'image_author' => $imageMedia['author'] ?? null,
            'tags' => $tags,
            'reading_time' => $news->readingTimeMinutes(),
            'image' => $imageUrl,
            'image_media' => $imageMedia,
            'tenant' => $news->tenant->shortname,
        ];
    }

    /**
     * @param  iterable<News>  $articles
     * @return list<array{id: int, title: string, short: string, lang: string, image: string|null, image_media: array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}|null, permalink: string|null, publish_time: string|null, url: string}>
     */
    public static function relatedArticles(iterable $articles, string $subdomain): array
    {
        $items = [];
        foreach ($articles as $article) {
            $items[] = self::relatedArticle($article, $subdomain);
        }

        return $items;
    }

    /** @return array{id: int, title: string, short: string, lang: string, image: string|null, image_media: array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}|null, permalink: string|null, publish_time: string|null, url: string} */
    private static function relatedArticle(News $article, string $subdomain): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'short' => $article->short,
            'lang' => $article->lang,
            'image' => $article->getImageUrl(),
            'image_media' => $article->imageData('image'),
            'permalink' => $article->permalink,
            'publish_time' => $article->publish_time?->toJSON(),
            'url' => LocalizedRouteSlugs::route('news', [
                'subdomain' => $subdomain,
                'news' => $article->permalink,
            ], $article->lang),
        ];
    }
}
