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

    /** @return array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, layer: LearningLayer, layerLabel: string, stage: LearningStage|null, importance: LearningImportance, title: string, description: string, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, actionUrl: string|null, state: 'pending'|'done'|'skipped'|'updated', autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, glossaryTerms: list<array{id: string, term: string, definition: string}>} */
    public static function step(ResolvedLearningStep $item, User $viewer, string $locale): array
    {
        $step = $item->step;
        $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
        $label = match ($step->layer) {
            LearningLayer::InstitutionType => (string) $step->institutionType?->getTranslation('title', $locale),
            LearningLayer::DutyType => (string) $step->dutyType?->getTranslation('title', $locale),
            LearningLayer::Tenant => $step->tenant->shortname ?? '',
            default => (string) __("learning.layers.{$step->layer->value}", [], $locale),
        };
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
    private static function source(LearningStep $step, User $viewer, string $locale): ?array
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

    /** @return list<array{id: string, title: string, kind: LearningStepKind, layer: LearningLayer, stage: LearningStage|null, importance: LearningImportance, isActive: bool}> */
    public static function library(string $locale): array
    {
        $result = [];
        foreach (LearningStep::query()->ordered()->get() as $step) {
            $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
            $result[] = [
                'id' => $step->id, 'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
                'kind' => $step->kind, 'layer' => $step->layer, 'stage' => $step->stage, 'importance' => $step->importance, 'isActive' => $step->is_active,
            ];
        }

        return $result;
    }

    /** @return array{id: string, title: array{lt: string, en: string}, description: array{lt: string, en: string}, kind: LearningStepKind, layer: LearningLayer, stage: LearningStage|null, importance: LearningImportance, institution_type_id: int|null, duty_type_id: int|null, tenant_id: int|null, per_term: bool, order: int, is_active: bool, is_system: bool, sources: array<string, array{document_id: int|null, url: string|null}>} */
    public static function edit(LearningStep $step): array
    {
        $sources = [];
        foreach ($step->sources as $source) {
            $sources[$source->locale] = ['document_id' => $source->document_id, 'url' => $source->url];
        }

        return [
            'id' => $step->id, 'title' => ['lt' => (string) $step->getTranslation('title', 'lt', false), 'en' => (string) $step->getTranslation('title', 'en', false)],
            'description' => ['lt' => (string) $step->getTranslation('description', 'lt', false), 'en' => (string) $step->getTranslation('description', 'en', false)],
            'kind' => $step->kind, 'layer' => $step->layer, 'stage' => $step->stage, 'importance' => $step->importance,
            'institution_type_id' => $step->institution_type_id, 'duty_type_id' => $step->duty_type_id, 'tenant_id' => $step->tenant_id,
            'per_term' => $step->per_term, 'order' => $step->order, 'is_active' => $step->is_active, 'is_system' => $step->is_system, 'sources' => $sources,
        ];
    }
}
