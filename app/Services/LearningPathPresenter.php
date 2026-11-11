<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\LearningStep;
use App\Models\LearningStepAudience;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningStep;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * @phpstan-type StepPayload array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, audienceLabel: string, audienceNotes: list<array{label: string, description: string}>, stage: LearningStage|null, importance: LearningImportance, section: array{id: string, title: string}|null, title: string, description: string, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null}|null, actionUrl: string|null, actionLabel: string|null, state: string, autoCompleted: bool, automaticallyTracked: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, glossaryTerms: list<array{id: string, term: string, abbreviation: string, definition: string}>}
 */
class LearningPathPresenter
{
    /** @return array{currentStage: LearningStage|null, termStart: string|null, stages: list<array{stage: LearningStage, steps: list<StepPayload>}>, meetingPrep: list<array{audience: array{id: string, label: string}, steps: list<StepPayload>}>, documents: array{essentialDone: int, essentialTotal: int, done: int, total: int, nextDocument: StepPayload|null}, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}} */
    public static function path(LearningPath $path, string $locale): array
    {
        (new EloquentCollection($path->steps->concat($path->documents)->concat($path->meetingPrep)->map(fn (ResolvedLearningStep $item): LearningStep => $item->step)->all()))->loadMissing('glossaryTerms');
        $stages = [];
        foreach (LearningStage::cases() as $stage) {
            $steps = [];
            foreach ($path->steps as $item) {
                if ($item->stage === $stage) {
                    $steps[] = self::step($item, auth()->user() ?? $path->user, $locale);
                }
            }
            $stages[] = ['stage' => $stage, 'steps' => $steps];
        }
        $meetingPrep = [];
        foreach ($path->meetingPrep->groupBy(fn (ResolvedLearningStep $item): string => $item->audienceKey) as $id => $items) {
            $steps = [];
            foreach ($items as $item) {
                $steps[] = self::step($item, auth()->user() ?? $path->user, $locale);
            }
            $meetingPrep[] = [
                'audience' => ['id' => $id, 'label' => $items->first()->audienceLabel],
                'steps' => $steps,
            ];
        }

        $nextDocument = $path->documents
            ->filter(fn (ResolvedLearningStep $item): bool => in_array($item->state, ['pending', 'updated'], true) && $item->importance !== LearningImportance::Optional)
            ->sortBy(fn (ResolvedLearningStep $item): int => $item->importance === LearningImportance::Essential ? 0 : 1)->first();

        return ['currentStage' => $path->currentStage, 'termStart' => $path->termStart?->toDateString(), 'stages' => $stages, 'meetingPrep' => $meetingPrep,
            'documents' => [...$path->documentTotals(), 'nextDocument' => $nextDocument ? self::step($nextDocument, auth()->user() ?? $path->user, $locale) : null],
            'totals' => $path->totals()];
    }

    /** @return array{sections: list<array{id: string, title: string, description: string, order: int}>, documents: list<StepPayload>} */
    public static function documents(LearningPath $path, string $locale): array
    {
        $items = $path->documents;
        (new EloquentCollection($items->map(fn (ResolvedLearningStep $item): LearningStep => $item->step)->all()))->loadMissing('glossaryTerms');
        $documents = [];
        foreach ($items as $item) {
            $documents[] = self::step($item, $path->user, $locale);
        }

        $sections = $items->map(fn (ResolvedLearningStep $item) => $item->step->section)->filter()->unique('id')->sortBy(['order', 'id'])
            ->map(fn ($section): array => ['id' => $section->id, 'title' => (string) $section->getTranslation('title', $locale),
                'description' => (string) $section->getTranslation('description', $locale), 'order' => $section->order])->values()->all();

        return ['sections' => $sections, 'documents' => $documents];
    }

    /** @return StepPayload */
    public static function step(ResolvedLearningStep $item, User $viewer, string $locale): array
    {
        $step = $item->step;
        $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
        $route = $definition === null ? null : match ($step->key) {
            'record_first_meeting' => $viewer->can('viewAny', Meeting::class) ? 'meetings.index' : null,
            default => null,
        };
        $actionUrl = $route ? route($route) : null;
        $actionLabel = $definition ? (string) __($definition['action'], [], $locale) : null;
        if ($definition === null && $step->kind === LearningStepKind::Action) {
            $actionUrl = filled($step->getTranslation('action_url', $locale)) ? (string) $step->getTranslation('action_url', $locale) : null;
            $actionLabel = filled($step->getTranslation('action_label', $locale)) ? (string) $step->getTranslation('action_label', $locale) : null;
        }
        $glossaryTerms = [];
        foreach ($step->loadMissing('glossaryTerms')->glossaryTerms as $term) {
            $glossaryTerms[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale), 'abbreviation' => (string) $term->getTranslation('abbreviation', $locale), 'definition' => (string) $term->getTranslation('definition', $locale)];
        }

        return [
            'id' => $step->id, 'key' => $step->key, 'isSystem' => $step->is_system,
            'kind' => $step->kind, 'audienceLabel' => $item->audienceLabel, 'audienceNotes' => $item->audienceNotes,
            'stage' => $item->stage, 'importance' => $item->importance,
            'section' => $step->section ? ['id' => $step->section->id, 'title' => (string) $step->section->getTranslation('title', $locale)] : null,
            'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
            // System text is plain; authored descriptions are rich HTML sanitized on write.
            'description' => $definition ? '<p>'.e(__($definition['description'], [], $locale)).'</p>' : (string) $step->getTranslation('description', $locale),
            'source' => self::source($step, $viewer, $locale),
            'actionUrl' => $actionUrl, 'actionLabel' => $actionLabel,
            'state' => $item->state, 'autoCompleted' => $item->autoCompleted,
            'automaticallyTracked' => ($definition['detector'] ?? null) !== null,
            'completedAt' => $item->completedAt?->toIso8601String(),
            'skippable' => $step->kind !== LearningStepKind::MeetingPrep && $item->importance->isSkippable(),
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

    /** @return list<array{id: string, title: string, kind: LearningStepKind, audiences: list<array{id: string, type: LearningAudienceType, label: string, importance: LearningImportance, stage: LearningStage|null}>, audienceTypes: list<LearningAudienceType>, section: array{id: string, title: string}|null, order: int, isActive: bool, canUpdate: bool, flags: list<'expired_document'|'updated_document'|'missing_source'>}> */
    public static function library(string $locale, ?User $viewer = null): array
    {
        $user = $viewer ?? auth()->user();
        $result = [];
        foreach (LearningStep::query()->ordered()->with(['sources.document', 'section', 'audiences' => fn ($query) => $query->with(LearningStepAudience::LABEL_RELATIONS)])->get() as $step) {
            $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
            $canUpdate = $user instanceof User && $user->can('update', $step);
            $result[] = [
                'id' => $step->id,
                'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
                'kind' => $step->kind,
                'audiences' => $step->audiences->map(fn (LearningStepAudience $audience): array => [
                    'id' => $audience->id, 'type' => $audience->type, 'label' => $audience->label($locale),
                    'importance' => $audience->importance, 'stage' => $audience->stage,
                ])->all(),
                'audienceTypes' => $step->audiences->pluck('type')->unique()->values()->all(),
                'section' => $step->section ? ['id' => $step->section->id, 'title' => (string) $step->section->getTranslation('title', $locale)] : null,
                'order' => $step->order,
                'isActive' => $step->is_active,
                'canUpdate' => $canUpdate,
                'flags' => ($user === null || $canUpdate) ? self::flags($step) : [],
            ];
        }

        return $result;
    }

    /** @return array{id: string, key: string|null, title: array{lt: string, en: string}, description: array{lt: string, en: string}, action_label: array{lt: string, en: string}, action_url: array{lt: string, en: string}, kind: LearningStepKind, learning_section_id: string|null, audiences: list<array{id: string, type: LearningAudienceType, source_id: string|null, tenant_id: int|null, importance: LearningImportance, stage: LearningStage|null, description: array{lt: string, en: string}, order: int, label: string}>, per_term: bool, order: int, is_active: bool, is_system: bool, sources: array<string, array{document_id: int|null, url: string|null}>, source_documents: array<string, array{id: int, title: string, status: string, date: string|null}>} */
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
            'action_label' => ['lt' => (string) $step->getTranslation('action_label', 'lt', false), 'en' => (string) $step->getTranslation('action_label', 'en', false)],
            'action_url' => ['lt' => (string) $step->getTranslation('action_url', 'lt', false), 'en' => (string) $step->getTranslation('action_url', 'en', false)],
            'kind' => $step->kind, 'learning_section_id' => $step->learning_section_id,
            'audiences' => $step->audiences->map(fn (LearningStepAudience $audience): array => [
                'id' => $audience->id, 'type' => $audience->type, 'source_id' => $audience->source_id,
                'tenant_id' => $audience->tenant_id, 'importance' => $audience->importance, 'stage' => $audience->stage,
                'description' => ['lt' => (string) $audience->getTranslation('description', 'lt', false), 'en' => (string) $audience->getTranslation('description', 'en', false)],
                'order' => $audience->order, 'label' => $audience->label(app()->getLocale()),
            ])->all(),
            'per_term' => $step->per_term, 'order' => $step->order, 'is_active' => $step->is_active, 'is_system' => $step->is_system,
            'sources' => $sources, 'source_documents' => $sourceDocuments,
        ];
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
