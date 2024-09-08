<?php
namespace App\Actions;

use App\Models\News;
use App\Models\Content;

class DuplicateNewsAction
{
    public function execute(News $news): News
    {
        // Replicate the news item
        $newNews = $news->replicate();

        // Modify the replicated news item
        $newNews->title .= ' (kopija)';
        $newNews->permalink .= '-kopija';
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
