<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Models\KnowledgeItem;
use App\Models\LearningAudience;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningItem;

/**
 * @phpstan-type KnowledgePayload array{id: string, title: string, description: string, audienceLabel: string, audienceNotes: list<array{label: string, description: string}>, importance: LearningImportance, section: array{id: string, title: string}|null, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, state: string, autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, perTerm: bool, glossaryTerms: list<array{id: string, term: string, abbreviation: string, definition: string}>}
 */
class KnowledgeBasePresenter
{
    /** @return array{sections: list<array{id: string, title: string, description: string, order: int}>, items: list<KnowledgePayload>} */
    public static function overview(LearningPath $path, string $locale): array
    {
        $payloads = [];
        $sections = [];
        foreach ($path->knowledge as $resolved) {
            /** @var KnowledgeItem $item */
            $item = $resolved->item;
            $payloads[] = self::item($resolved, $path->user, $locale);
            if ($item->section !== null) {
                $section = $item->section;
                $sections[$section->id] = ['id' => $section->id, 'title' => (string) $section->getTranslation('title', $locale),
                    'description' => (string) $section->getTranslation('description', $locale), 'order' => $section->order];
            }
        }
        $sections = array_values($sections);
        usort($sections, fn (array $a, array $b): int => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]);

        return ['sections' => $sections, 'items' => $payloads];
    }

    /** @return KnowledgePayload */
    public static function item(ResolvedLearningItem $resolved, User $viewer, string $locale): array
    {
        /** @var KnowledgeItem $item */
        $item = $resolved->item;
        $terms = [];
        foreach ($item->loadMissing('glossaryTerms')->glossaryTerms as $term) {
            $terms[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale),
                'abbreviation' => (string) $term->getTranslation('abbreviation', $locale), 'definition' => (string) $term->getTranslation('definition', $locale)];
        }

        return [
            'id' => $item->id, 'title' => (string) $item->getTranslation('title', $locale),
            'description' => (string) $item->getTranslation('description', $locale),
            'audienceLabel' => $resolved->audienceLabel, 'audienceNotes' => $resolved->audienceNotes,
            'importance' => $resolved->importance,
            'section' => $item->section ? ['id' => $item->section->id, 'title' => (string) $item->section->getTranslation('title', $locale)] : null,
            'source' => self::source($item, $viewer, $locale), 'state' => $resolved->state,
            'autoCompleted' => false, 'completedAt' => $resolved->completedAt?->toIso8601String(),
            'skippable' => $resolved->importance->isSkippable(), 'insightsCount' => (int) $item->comments_count,
            'reviewedAt' => $item->reviewed_at?->toDateString(), 'perTerm' => $item->per_term, 'glossaryTerms' => $terms,
        ];
    }

    /** @return array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null */
    public static function source(KnowledgeItem $step, User $viewer, string $locale): ?array
    {
        $source = $step->sourceFor($locale);
        if ($source === null) {
            return null;
        }
        $document = $source->document;
        if ($document !== null) {
            $url = $document->isPublished() && filled($document->anonymous_url)
                ? route('documents.show', $document->id)
                : ($viewer->can('update', $document) ? route('documents.edit', $document->id) : null);

            return ['type' => 'document', 'url' => $url, 'documentId' => $document->id, 'documentStatus' => $document->status, 'title' => $document->title];
        }
        if ($source->url !== null) {
            return ['type' => 'url', 'url' => $source->url, 'documentId' => null, 'documentStatus' => null, 'title' => null];
        }

        return null;
    }

    /** @return list<array{id: string, title: string, audiences: list<array{id: string, type: LearningAudienceType, label: string, importance: LearningImportance, stage: LearningStage|null}>, audienceTypes: list<LearningAudienceType>, section: array{id: string, title: string}|null, order: int, isActive: bool, canUpdate: bool, perTerm: bool, flags: list<'expired_document'|'updated_document'|'missing_source'>}> */
    public static function library(string $locale, ?User $viewer = null): array
    {
        $user = $viewer ?? auth()->user();
        $result = [];
        foreach (KnowledgeItem::query()->ordered()->with(['sources.document', 'section', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->get() as $step) {
            $canUpdate = $user instanceof User && $user->can('update', $step);
            $result[] = [
                'id' => $step->id,
                'title' => (string) $step->getTranslation('title', $locale),
                'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                    'id' => $audience->id, 'type' => $audience->type, 'label' => $audience->label($locale),
                    'importance' => $audience->importance, 'stage' => $audience->stage,
                ])->all(),
                'audienceTypes' => $step->audiences->pluck('type')->unique()->values()->all(),
                'section' => $step->section ? ['id' => $step->section->id, 'title' => (string) $step->section->getTranslation('title', $locale)] : null,
                'perTerm' => $step->per_term,
                'order' => $step->order,
                'isActive' => $step->is_active,
                'canUpdate' => $canUpdate,
                'flags' => ($user === null || $canUpdate) ? self::flags($step) : [],
            ];
        }

        return $result;
    }

    /** @return array{id: string, title: array{lt: string, en: string}, description: array{lt: string, en: string}, knowledge_section_id: string|null, audiences: list<array{id: string, type: LearningAudienceType, source_id: string|null, tenant_id: int|null, importance: LearningImportance, stage: LearningStage|null, description: array{lt: string, en: string}, order: int, label: string}>, per_term: bool, order: int, is_active: bool, sources: array<string, array{document_id: int|null, url: string|null}>, source_documents: array<string, array{id: int, title: string, status: string, date: string|null}>} */
    public static function edit(KnowledgeItem $step): array
    {
        $sources = [];
        $sourceDocuments = [];
        foreach ($step->sources as $source) {
            $sources[$source->locale] = ['document_id' => $source->document_id, 'url' => $source->url];
            if ($source->document !== null) {
                $sourceDocuments[$source->locale] = [
                    'id' => $source->document->id,
                    'title' => (string) $source->document->title,
                    'status' => $source->document->status->value,
                    'date' => $source->document->document_date?->toDateString(),
                ];
            }
        }

        return [
            'id' => $step->id,
            'title' => ['lt' => (string) $step->getTranslation('title', 'lt', false), 'en' => (string) $step->getTranslation('title', 'en', false)],
            'description' => ['lt' => (string) $step->getTranslation('description', 'lt', false), 'en' => (string) $step->getTranslation('description', 'en', false)],
            'knowledge_section_id' => $step->knowledge_section_id,
            'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                'id' => $audience->id, 'type' => $audience->type, 'source_id' => $audience->source_id,
                'tenant_id' => $audience->tenant_id, 'importance' => $audience->importance, 'stage' => $audience->stage,
                'description' => ['lt' => (string) $audience->getTranslation('description', 'lt', false), 'en' => (string) $audience->getTranslation('description', 'en', false)],
                'order' => $audience->order, 'label' => $audience->label(app()->getLocale()),
            ])->all(),
            'per_term' => $step->per_term, 'order' => $step->order, 'is_active' => $step->is_active,
            'sources' => $sources, 'source_documents' => $sourceDocuments,
        ];
    }

    /** @return list<'expired_document'|'updated_document'|'missing_source'> */
    private static function flags(KnowledgeItem $step): array
    {
        $flags = [];
        $baseline = $step->reviewed_at ?? $step->updated_at;
        $documents = $step->sources->map(fn ($source) => $source->document)->filter();

        if ($documents->contains(fn ($document): bool => $document->is_in_effect === false)) {
            $flags[] = 'expired_document';
        }
        if ($documents->contains(fn ($document): bool => $document->sharepoint_modified_at !== null && ($baseline === null || $document->sharepoint_modified_at->gt($baseline)))) {
            $flags[] = 'updated_document';
        }
        if ($step->sources->filter(fn ($source): bool => $source->document_id !== null || filled($source->url))->isEmpty()) {
            $flags[] = 'missing_source';
        }

        return $flags;
    }
}
