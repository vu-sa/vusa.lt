<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\LearningImportance;
use App\Enums\LearningLayer;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\Institution;
use App\Models\LearningStep;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningStep;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LearningPathPresenter
{
    /** @return array{currentStage: LearningStage|null, termStart: string|null, stages: list<array{stage: LearningStage, steps: list<array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, layer: LearningLayer, layerLabel: string, stage: LearningStage|null, importance: LearningImportance, title: string, description: string, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, actionUrl: string|null, state: 'pending'|'done'|'skipped'|'updated', autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, glossaryTerms: list<array{id: string, term: string, definition: string}>}>}>, meetingPrep: list<array{institutionType: array{id: int, title: string}, steps: list<array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, layer: LearningLayer, layerLabel: string, stage: LearningStage|null, importance: LearningImportance, title: string, description: string, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, actionUrl: string|null, state: 'pending'|'done'|'skipped'|'updated', autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, glossaryTerms: list<array{id: string, term: string, definition: string}>}>}>, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}} */
    public static function path(LearningPath $path, string $locale): array
    {
        (new EloquentCollection($path->steps->concat($path->meetingPrep)->map(fn (ResolvedLearningStep $item): LearningStep => $item->step)->all()))->loadMissing('glossaryTerms');
        $stages = [];
        foreach (LearningStage::cases() as $stage) {
            $steps = [];
            foreach ($path->steps as $item) {
                if ($item->step->stage === $stage) {
                    $steps[] = self::step($item, auth()->user() ?? $path->user, $locale);
                }
            }
            $stages[] = ['stage' => $stage, 'steps' => $steps];
        }
        $meetingPrep = [];
        foreach ($path->meetingPrep->groupBy(fn (ResolvedLearningStep $item): int => $item->step->institution_type_id) as $id => $items) {
            $steps = [];
            foreach ($items as $item) {
                $steps[] = self::step($item, auth()->user() ?? $path->user, $locale);
            }
            $meetingPrep[] = [
                'institutionType' => ['id' => (int) $id, 'title' => (string) $items->first()->step->institutionType?->getTranslation('title', $locale)],
                'steps' => $steps,
            ];
        }

        return ['currentStage' => $path->currentStage, 'termStart' => $path->termStart?->toDateString(), 'stages' => $stages, 'meetingPrep' => $meetingPrep, 'totals' => $path->totals()];
    }

    /** @return list<array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, layer: LearningLayer, layerLabel: string, stage: LearningStage|null, importance: LearningImportance, title: string, description: string, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, actionUrl: string|null, state: 'pending'|'done'|'skipped'|'updated', autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, glossaryTerms: list<array{id: string, term: string, definition: string}>}> */
    public static function documents(LearningPath $path, string $locale): array
    {
        $items = $path->steps->filter(fn (ResolvedLearningStep $item): bool => $item->step->kind === LearningStepKind::Knowledge);
        (new EloquentCollection($items->map(fn (ResolvedLearningStep $item): LearningStep => $item->step)->all()))->loadMissing('glossaryTerms');
        $documents = [];
        foreach ($items as $item) {
            $documents[] = self::step($item, $path->user, $locale);
        }

        return $documents;
    }

    /** @return array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, layer: LearningLayer, layerLabel: string, stage: LearningStage|null, importance: LearningImportance, title: string, description: string, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, actionUrl: string|null, state: 'pending'|'done'|'skipped'|'updated', autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, glossaryTerms: list<array{id: string, term: string, definition: string}>} */
    public static function step(ResolvedLearningStep $item, User $viewer, string $locale): array
    {
        $step = $item->step;
        $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
        $label = self::layerLabel($step, $locale);
        $route = $definition['actionRoute'] ?? null;
        if ($definition !== null && $route === null) {
            $route = match ($step->key) {
                'read_handover', 'write_handover' => 'learningPath.handover',
                'follow_institution' => $viewer->can('viewAny', Institution::class) ? 'institutions.index' : null,
                'record_first_meeting' => $viewer->can('viewAny', Meeting::class) ? 'meetings.index' : null,
                default => null,
            };
        }
        $glossaryTerms = [];
        foreach ($step->loadMissing('glossaryTerms')->glossaryTerms as $term) {
            $glossaryTerms[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale), 'definition' => (string) $term->getTranslation('definition', $locale)];
        }

        return [
            'id' => $step->id, 'key' => $step->key, 'isSystem' => $step->is_system,
            'kind' => $step->kind, 'layer' => $step->layer, 'layerLabel' => $label,
            'stage' => $step->stage, 'importance' => $step->importance,
            'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
            'description' => $definition ? (string) __($definition['description'], [], $locale) : (string) $step->getTranslation('description', $locale),
            'source' => self::source($step, $viewer, $locale),
            'actionUrl' => $route ? route($route) : null,
            'state' => $item->state, 'autoCompleted' => $item->autoCompleted,
            'completedAt' => $item->completedAt?->toIso8601String(),
            'skippable' => $step->kind !== LearningStepKind::MeetingPrep && $step->importance->isSkippable(),
            'insightsCount' => (int) $step->comments_count,
            'reviewedAt' => $step->reviewed_at?->toDateString(),
            'glossaryTerms' => $glossaryTerms,
        ];
    }

    /** @return array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null */
    public static function source(LearningStep $step, User $viewer, string $locale): ?array
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

    /** @return list<array{id: string, title: string, kind: LearningStepKind, layer: LearningLayer, layerLabel: string, stage: LearningStage|null, importance: LearningImportance, isActive: bool, canUpdate: bool, flags: list<'expired_document'|'updated_document'|'missing_source'>}> */
    public static function library(string $locale, ?User $viewer = null): array
    {
        $user = $viewer ?? auth()->user();
        $result = [];
        foreach (LearningStep::query()->ordered()->with(['sources.document', 'institutionType', 'dutyType', 'tenant'])->get() as $step) {
            $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
            $canUpdate = $user instanceof User && $user->can('update', $step);
            $result[] = [
                'id' => $step->id,
                'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
                'kind' => $step->kind,
                'layer' => $step->layer,
                'layerLabel' => self::layerLabel($step, $locale),
                'stage' => $step->stage,
                'importance' => $step->importance,
                'isActive' => $step->is_active,
                'canUpdate' => $canUpdate,
                'flags' => ($user === null || $canUpdate) ? self::flags($step) : [],
            ];
        }

        return $result;
    }

    /** @return array{id: string, key: string|null, title: array{lt: string, en: string}, description: array{lt: string, en: string}, kind: LearningStepKind, layer: LearningLayer, stage: LearningStage|null, importance: LearningImportance, institution_type_id: int|null, duty_type_id: int|null, tenant_id: int|null, per_term: bool, order: int, is_active: bool, is_system: bool, sources: array<string, array{document_id: int|null, url: string|null}>, source_documents: array<string, array{id: int, title: string, status: string, date: string|null}>} */
    public static function edit(LearningStep $step): array
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
            'id' => $step->id, 'key' => $step->key,
            'title' => ['lt' => (string) $step->getTranslation('title', 'lt', false), 'en' => (string) $step->getTranslation('title', 'en', false)],
            'description' => ['lt' => (string) $step->getTranslation('description', 'lt', false), 'en' => (string) $step->getTranslation('description', 'en', false)],
            'kind' => $step->kind, 'layer' => $step->layer, 'stage' => $step->stage, 'importance' => $step->importance,
            'institution_type_id' => $step->institution_type_id, 'duty_type_id' => $step->duty_type_id, 'tenant_id' => $step->tenant_id,
            'per_term' => $step->per_term, 'order' => $step->order, 'is_active' => $step->is_active, 'is_system' => $step->is_system,
            'sources' => $sources, 'source_documents' => $sourceDocuments,
        ];
    }

    private static function layerLabel(LearningStep $step, string $locale): string
    {
        return match ($step->layer) {
            LearningLayer::InstitutionType => (string) $step->institutionType?->getTranslation('title', $locale),
            LearningLayer::DutyType => (string) $step->dutyType?->getTranslation('title', $locale),
            LearningLayer::Tenant => $step->tenant->shortname ?? '',
            default => (string) __("learning.layers.{$step->layer->value}", [], $locale),
        };
    }

    /** @return list<'expired_document'|'updated_document'|'missing_source'> */
    private static function flags(LearningStep $step): array
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
        if ($step->kind === LearningStepKind::Knowledge && $step->sources->filter(fn ($source): bool => $source->document_id !== null || filled($source->url))->isEmpty()) {
            $flags[] = 'missing_source';
        }

        return $flags;
    }
}
