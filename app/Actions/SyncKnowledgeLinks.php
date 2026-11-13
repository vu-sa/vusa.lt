<?php

namespace App\Actions;

use App\Models\KnowledgeItem;

/**
 * Rebuilds the page-to-page link index from the `[[` links in a page body, so related pages and
 * backlinks never need hand-kept relations.
 */
class SyncKnowledgeLinks
{
    public static function execute(KnowledgeItem $item): void
    {
        if (! $item->wasRecentlyCreated && ! $item->wasChanged('description')) {
            return;
        }

        $ids = array_values(array_diff(self::idsIn(...array_values($item->getTranslations('description'))), [$item->id]));
        $item->outgoingLinks()->sync(KnowledgeItem::query()->whereKey($ids)->pluck('id')->all());
    }

    /** @return list<string> */
    public static function idsIn(?string ...$html): array
    {
        preg_match_all('/data-knowledge-id="([0-9A-Za-z]{26})"/', implode("\n", array_filter($html)), $matches);

        return array_values(array_unique(array_map(strtolower(...), $matches[1])));
    }
}
