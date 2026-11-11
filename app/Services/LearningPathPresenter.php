<?php

namespace App\Services;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\LearningAudience;
use App\Models\LearningStep;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningItem;
use App\Services\Learning\SystemSteps\SystemStepRegistry;

/**
 * @phpstan-import-type KnowledgePayload from KnowledgeBasePresenter
 *
 * @phpstan-type StepPayload array{id: string, key: string|null, isSystem: bool, kind: LearningStepKind, audienceLabel: string, audienceNotes: list<array{label: string, description: string}>, stage: LearningStage|null, importance: LearningImportance, title: string, description: string, actionUrl: string|null, actionLabel: string|null, state: string, autoCompleted: bool, automaticallyTracked: bool, completedAt: string|null, skippable: bool, reviewedAt: string|null}
 */
class LearningPathPresenter
{
    /** @return array{currentStage: LearningStage|null, termStart: string|null, stages: list<array{stage: LearningStage, steps: list<StepPayload>}>, meetingPrep: list<array{audience: array{id: string, label: string}, steps: list<StepPayload>}>, knowledge: array{essentialDone: int, essentialTotal: int, done: int, total: int, nextItem: KnowledgePayload|null, priorityProgress: list<array{importance: LearningImportance, done: int, total: int}>}, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}} */
    public static function path(LearningPath $path, string $locale): array
    {
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
        foreach ($path->meetingPrep->groupBy(fn (ResolvedLearningItem $item): string => $item->audienceKey) as $id => $items) {
            $steps = [];
            foreach ($items as $item) {
                $steps[] = self::step($item, auth()->user() ?? $path->user, $locale);
            }
            $meetingPrep[] = [
                'audience' => ['id' => $id, 'label' => $items->first()->audienceLabel],
                'steps' => $steps,
            ];
        }

        $nextItem = $path->knowledge
            ->filter(fn (ResolvedLearningItem $item): bool => in_array($item->state, ['pending', 'updated'], true) && $item->importance !== LearningImportance::Optional)
            ->sortBy(fn (ResolvedLearningItem $item): int => $item->importance === LearningImportance::Essential ? 0 : 1)->first();

        return ['currentStage' => $path->currentStage, 'termStart' => $path->termStart?->toDateString(), 'stages' => $stages, 'meetingPrep' => $meetingPrep,
            'knowledge' => [...$path->knowledgeTotals(), 'nextItem' => $nextItem ? KnowledgeBasePresenter::item($nextItem, $locale) : null,
                'priorityProgress' => KnowledgeBasePresenter::assignedProgress($path)],
            'totals' => $path->totals()];
    }

    /** @return StepPayload */
    public static function step(ResolvedLearningItem $item, User $viewer, string $locale): array
    {
        /** @var LearningStep $step */
        $step = $item->item;
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

        return [
            'id' => $step->id, 'key' => $step->key, 'isSystem' => $step->is_system,
            'kind' => $step->kind, 'audienceLabel' => $item->audienceLabel, 'audienceNotes' => $item->audienceNotes,
            'stage' => $item->stage, 'importance' => $item->importance,
            'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
            // System text is plain; authored descriptions are rich HTML sanitized on write.
            'description' => $definition ? '<p>'.e(__($definition['description'], [], $locale)).'</p>' : (string) $step->getTranslation('description', $locale),
            'actionUrl' => $actionUrl, 'actionLabel' => $actionLabel,
            'state' => $item->state, 'autoCompleted' => $item->autoCompleted,
            'automaticallyTracked' => ($definition['detector'] ?? null) !== null,
            'completedAt' => $item->completedAt?->toIso8601String(),
            'skippable' => $step->kind !== LearningStepKind::MeetingPrep && $item->importance->isSkippable(),
            'reviewedAt' => $step->reviewed_at?->toDateString(),
        ];
    }

    /** @return list<array{id: string, title: string, kind: LearningStepKind, audiences: list<array{id: string, type: LearningAudienceType, label: string, importance: LearningImportance, stage: LearningStage|null}>, audienceTypes: list<LearningAudienceType>, order: int, isActive: bool, canUpdate: bool}> */
    public static function library(string $locale, ?User $viewer = null): array
    {
        $user = $viewer ?? auth()->user();
        $result = [];
        foreach (LearningStep::query()->ordered()->with(['audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->get() as $step) {
            $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
            $canUpdate = $user instanceof User && $user->can('update', $step);
            $result[] = [
                'id' => $step->id,
                'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
                'kind' => $step->kind,
                'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                    'id' => $audience->id, 'type' => $audience->type, 'label' => $audience->label($locale),
                    'importance' => $audience->importance, 'stage' => $audience->stage,
                ])->all(),
                'audienceTypes' => $step->audiences->pluck('type')->unique()->values()->all(),
                'order' => $step->order,
                'isActive' => $step->is_active,
                'canUpdate' => $canUpdate,
            ];
        }

        return $result;
    }

    /** @return array{id: string, key: string|null, title: array{lt: string, en: string}, description: array{lt: string, en: string}, action_label: array{lt: string, en: string}, action_url: array{lt: string, en: string}, kind: LearningStepKind, audiences: list<array{id: string, type: LearningAudienceType, source_id: string|null, tenant_id: int|null, importance: LearningImportance, stage: LearningStage|null, description: array{lt: string, en: string}, order: int, label: string}>, order: int, is_active: bool, is_system: bool} */
    public static function edit(LearningStep $step): array
    {
        return [
            'id' => $step->id, 'key' => $step->key,
            'title' => ['lt' => (string) $step->getTranslation('title', 'lt', false), 'en' => (string) $step->getTranslation('title', 'en', false)],
            'description' => ['lt' => (string) $step->getTranslation('description', 'lt', false), 'en' => (string) $step->getTranslation('description', 'en', false)],
            'action_label' => ['lt' => (string) $step->getTranslation('action_label', 'lt', false), 'en' => (string) $step->getTranslation('action_label', 'en', false)],
            'action_url' => ['lt' => (string) $step->getTranslation('action_url', 'lt', false), 'en' => (string) $step->getTranslation('action_url', 'en', false)],
            'kind' => $step->kind,
            'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                'id' => $audience->id, 'type' => $audience->type, 'source_id' => $audience->source_id,
                'tenant_id' => $audience->tenant_id, 'importance' => $audience->importance, 'stage' => $audience->stage,
                'description' => ['lt' => (string) $audience->getTranslation('description', 'lt', false), 'en' => (string) $audience->getTranslation('description', 'en', false)],
                'order' => $audience->order, 'label' => $audience->label(app()->getLocale()),
            ])->all(),
            'order' => $step->order, 'is_active' => $step->is_active, 'is_system' => $step->is_system,
        ];
    }
}
