<?php

namespace App\Services;

use App\Enums\ContentPartEnum;
use App\Models\Content;
use App\Models\ContentPart;
use Illuminate\Support\Collection;
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
        /** @var Collection<int, ContentPart> $existingPartsById */
        $existingPartsById = $content->parts()->get()->keyBy('id');

        // Snapshotted separately from $existingPartsById: the loop below
        // mutates those SAME ContentPart instances in place (order/type/etc.),
        // so reading ->order off them afterwards would already reflect the
        // NEW value, not what it was before this save.
        $originalOrderById = $existingPartsById->map(fn (ContentPart $part) => $part->order)->all();

        // Track which IDs we've processed, and the `order` each survivor ends
        // up with -- used below to detect a reorder even when a part's
        // position relative to its *siblings* is unchanged (inserting a new
        // block at the front still renumbers every existing block's order).
        $handledIds = [];
        $newOrderById = [];

        foreach ($contentParts as $index => $partData) {
            // Skip null parts
            if (is_null($partData)) {
                continue;
            }

            $id = $partData['id'] ?? null;

            // Validate content type — must hold for updates too, not just new parts.
            // Form Request validation already covers this on the store/update HTTP
            // paths, but this method is also reachable directly (seeders, commands).
            if (! in_array($partData['type'], ContentPartEnum::toArray())) {
                Log::warning("Invalid content part type: {$partData['type']}");

                // An existing part with a rejected update must still count as "handled",
                // or it gets swept up by the deletion pass below for simply not having
                // been touched — rejecting a bad edit should leave the part as-is, not
                // delete it. Its order is untouched too, so record the unchanged value.
                if ($id && isset($existingPartsById[$id])) {
                    $handledIds[] = $id;
                    $newOrderById[$id] = $originalOrderById[$id];
                }

                continue;
            }

            // Check if we're updating an existing part or creating a new one
            if ($id && isset($existingPartsById[$id])) {
                // Update existing part
                /** @var ContentPart $part */
                $part = $existingPartsById[$id];
                $part->type = $partData['type'];
                $part->json_content = $partData['json_content'];
                $part->options = $partData['options'] ?? null;
                $part->order = $index;
                $part->save();

                $handledIds[] = $id;
                $newOrderById[$id] = $index;
            } else {
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
            // Deleting through the relation's query builder is a mass delete
            // and fires no model events, so removed blocks would never reach
            // the activity log. Volume here is a handful of rows per save.
            $content->parts()->whereIn('id', $idsToDelete)->get()->each->delete();
        }

        $this->logReorderIfChanged($content, $originalOrderById, $newOrderById);

        return $content->fresh('parts');
    }

    /**
     * Per-block `order`-only changes are suppressed (see
     * ContentPart::getActivitylogOptions()) to avoid one near-identical
     * activity per sibling on every insert, delete, or drag-reorder. This
     * restores a single trace of it: one activity on the owning
     * News/Page/Tenant when any surviving block's `order` actually changed
     * value -- which also covers inserting a block at the front, since that
     * renumbers every sibling after it even though their relative sequence
     * among themselves is untouched. Created/deleted blocks already have
     * their own `created`/`deleted` activity, so only ids present both
     * before and after this save are compared.
     *
     * @param  array<int, int>  $originalOrderById  Surviving part id => the
     *                                              `order` it had before this save.
     * @param  array<int, int>  $newOrderById  Surviving part id => the `order`
     *                                         it has after this save.
     */
    protected function logReorderIfChanged(Content $content, array $originalOrderById, array $newOrderById): void
    {
        if (count($newOrderById) < 2) {
            return;
        }
        $changed = array_any($newOrderById, fn ($newOrder, $id) => (int) $originalOrderById[$id] !== (int) $newOrder);

        if (! $changed) {
            return;
        }

        $owner = $content->owner();

        if ($owner === null) {
            return;
        }

        activity()
            ->performedOn($owner)
            ->event('content_reordered')
            ->withProperties(['count' => count($newOrderById)])
            ->log('content_reordered');
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
