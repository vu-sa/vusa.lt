<?php

namespace App\Actions;

use App\Models\Document;
use App\Models\Meeting;

/**
 * The nutarimai and protokolai of a meeting, as the public may see them.
 *
 * Limited to documents SharePoint actually shares — the same rule
 * Document::shouldBeSearchable() applies outside the admin context.
 */
class GetPublicMeetingDocuments
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function execute(Meeting $meeting): array
    {
        return $meeting->documents()
            ->whereNotNull('anonymous_url')
            ->where('anonymous_url', '!=', '')
            ->orderBy('document_date')
            ->orderBy('title')
            ->get(['id', 'title', 'name', 'content_type', 'document_date', 'anonymous_url', 'language'])
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title ?: $document->name,
                'content_type' => $document->content_type,
                'document_date' => $document->document_date?->toDateString(),
                'anonymous_url' => $document->anonymous_url,
                'language' => $document->language,
            ])
            ->all();
    }
}
