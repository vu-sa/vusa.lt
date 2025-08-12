<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Support\Facades\Log;

class ContentService
{
    /**
     * Update content parts efficiently
     *
     * Processes an array of content part data to create, update, or delete
     * content parts associated with a content model.
     *
     * @param  Content  $content  The content model to update parts for
     * @param  array  $contentParts  Array of content part data with keys:
     *                               - id: ?int The ID of an existing content part (null for new parts)
     *                               - type: string Content type identifier (must exist in ContentPartEnum)
     *                               - json_content: array|object The structured content data
     *                               - options: ?array Optional configuration settings
     * @return Content The updated content model with fresh parts relation
     */
    public function updateContentParts(Content $content, array $contentParts): Content
    {
        // First, collect existing parts by ID for efficient lookup
        /** @var \Illuminate\Support\Collection<int, \App\Models\ContentPart> $existingPartsById */
        $existingPartsById = $content->parts()->get()->keyBy('id');

        // Track which IDs we've processed
        $handledIds = [];

        foreach ($contentParts as $index => $partData) {
            // Skip null parts
            if (is_null($partData)) {
                continue;
            }

            $id = $partData['id'] ?? null;

            // Check if we're updating an existing part or creating a new one
            if ($id && isset($existingPartsById[$id])) {
                // Update existing part
                /** @var \App\Models\ContentPart $part */
                $part = $existingPartsById[$id];
                $part->type = $partData['type'];
                $part->json_content = $partData['json_content'];
                $part->options = $partData['options'] ?? null;
                $part->order = $index;
                $part->save();

                $handledIds[] = $id;
            } else {
                // Validate content type
                if (! in_array($partData['type'], \App\Enums\ContentPartEnum::toArray())) {
                    Log::warning("Invalid content part type: {$partData['type']}");

                    continue;
                }

                // Create new part
                $content->parts()->create([
                    'type' => $partData['type'],
                    'json_content' => $partData['json_content'],
                    'options' => $partData['options'] ?? null,
                    'order' => $index,
                ]);
            }
        }

        // Delete parts that weren't in the updated data
        $idsToDelete = $existingPartsById->keys()
            ->diff($handledIds)
            ->toArray();

        if (! empty($idsToDelete)) {
            $content->parts()->whereIn('id', $idsToDelete)->delete();
        }

        return $content->fresh('parts');
    }

    /**
     * Generate searchable content from a Content object
     */
    public function generateSearchableContent(Content $content): string
    {
        $searchableContent = '';

        // Iterate through all content parts and extract text
        foreach ($content->parts as $part) {
            $searchableContent .= $part->getSearchableContent().' ';
        }

        return trim($searchableContent);
    }
}
