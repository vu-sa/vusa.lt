<?php

namespace App\Actions;

use App\Models\Content;
use App\Models\News;
use Illuminate\Support\Str;

class DuplicateNewsAction
{
    public function execute(News $news): News
    {
        // Replicate the news item
        $newNews = $news->replicate();

        // Modify the replicated news item
        $newNews->title .= ' (kopija)';
        // Create url friendly 8 letter string
        $newNews->permalink .= '-' . Str::random(8);
        $newNews->draft = 1;
        $newNews->publish_time = null;

        // Create and save new content, disassociating it from the original news item
        $content = Content::create();
        $newNews->content_id = $content->id;
        $newNews->save();
        $newNews->refresh();

        // Copy content parts
        $contentParts = $news->content->parts->map(function ($part) {
            return [
                'type' => $part->type,
                'json_content' => $part->json_content,
                'options' => $part->options,
                'order' => $part->order,
            ];
        })->toArray();

        $newNews->content->parts()->createMany($contentParts);

        return $newNews;
    }
}
