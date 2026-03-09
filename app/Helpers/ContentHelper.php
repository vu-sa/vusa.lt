<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Tiptap\Editor;

class ContentHelper
{
    /**
     * Get the first tiptap element from content parts safely
     *
     * @param  mixed  $content  The content object or null
     * @return mixed The first tiptap content part or null
     */
    public static function getFirstTiptapElement($content)
    {
        $firstTiptapElement = $content?->parts?->filter(function ($part) {
            return $part->type === 'tiptap';
        })->first();

        // Check if empty array - this comes up when in content creation,
        // user doesn't add any content to tiptap editor. It is initialised
        // as an empty array, and when the ->setContent() method is called, it throws an error
        if ($firstTiptapElement && $firstTiptapElement->json_content === []) {
            $firstTiptapElement = null;
        }

        return $firstTiptapElement;
    }

    /**
     * Get description text for SEO purposes
     * For news: prioritize 'short' field (HTML), then fall back to first tiptap content
     * For pages: use first tiptap content
     *
     * @param  mixed  $model  The model (News or Page)
     * @param  int  $limit  Character limit for the description
     */
    public static function getDescriptionForSeo($model, int $limit = 160): ?string
    {
        // For news, prioritize the 'short' field if it exists and is not empty
        if (isset($model->short) && ! empty(trim(strip_tags($model->short)))) {
            return Str::limit(strip_tags($model->short), $limit);
        }

        // Fall back to first tiptap element
        $firstTiptapElement = self::getFirstTiptapElement($model->content);

        if ($firstTiptapElement) {
            // Convert ArrayObject to array if needed (Laravel casts JSON columns to ArrayObject)
            $jsonContent = $firstTiptapElement->json_content;
            if ($jsonContent instanceof \ArrayObject) {
                $jsonContent = $jsonContent->getArrayCopy();
            }

            return Str::limit((new Editor)->setContent($jsonContent)->getText(), $limit);
        }

        return null;
    }
}
