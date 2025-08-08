<?php

namespace App\Actions;

use App\Models\Content;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateNewsAction
{
    public static function execute(News $news): News
    {
        return DB::transaction(function () use ($news) {
            // Eager load relationships to avoid N+1 queries
            $news->load(['content.parts', 'tags']);

            // Replicate the news item
            $newNews = $news->replicate();

            // Modify the replicated news item
            $newNews->title = ($newNews->title ?? '').' '.__('(kopija)');
            // Create url friendly 8 letter string
            $newNews->permalink = ($newNews->permalink ?? '').'-'.Str::random(8);
            $newNews->draft = 1;
            $newNews->publish_time = null;

            // Create and save new content, disassociating it from the original news item
            $content = Content::create();
            $newNews->content_id = $content->id;
            $newNews->save();
            $newNews->refresh();

            // Copy content parts if they exist
            $contentParts = $news->content->parts->map(function ($part) {
                return [
                    'type' => $part->type,
                    'json_content' => $part->json_content,
                    'options' => $part->options,
                    'order' => $part->order,
                ];
            })->toArray();

            if (! empty($contentParts)) {
                $newNews->content->parts()->createMany($contentParts);
            }

            // Copy tags relationship
            if ($news->tags->isNotEmpty()) {
                $tagIds = $news->tags->pluck('id')->toArray();
                $newNews->tags()->attach($tagIds);
            }

            return $newNews;
        });
    }
}
