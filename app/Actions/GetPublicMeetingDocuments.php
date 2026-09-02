<?php

namespace App\Actions;

use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Support\Collection;

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
        $documents = $meeting->documents()
            ->whereNotNull('anonymous_url')
            ->where('anonymous_url', '!=', '')
            ->orderBy('document_date')
            ->orderBy('title')
            ->get(['id', 'title', 'name', 'content_type', 'document_date', 'anonymous_url', 'language']);

        return self::forLocale($documents, app()->getLocale())
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title ?: $document->name,
                'content_type' => $document->content_type,
                'document_date' => $document->document_date?->toDateString(),
                'anonymous_url' => $document->anonymous_url,
                'language' => $document->language,
                'language_code' => $document->language_code,
            ])
            ->values()
            ->all();
    }

    /**
     * SharePoint records each file's language, so the English page shows the English
     * paperwork. Documents of unknown language always survive the filter, and a meeting
     * whose files exist only in the other language keeps showing them — an empty list
     * would read as "no documents" rather than "none in your language".
     *
     * @param  Collection<int, Document>  $documents
     * @return Collection<int, Document>
     */
    private static function forLocale(Collection $documents, string $locale): Collection
    {
        $matching = $documents->filter(
            fn (Document $document): bool => in_array($document->language_code, [$locale, 'unknown'], true)
        );

        return $matching->isEmpty() ? $documents : $matching;
    }
}
