<?php

namespace App\Services;

use App\Actions\ResolveLearningPath;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\KnowledgeItem;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Learning\ResolvedLearningItem;
use Illuminate\Support\Collection;

/**
 * @phpstan-type TermPayload array{id: string, term: string, abbreviation: string, definition: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}}
 * @phpstan-type GlossaryPayload array{id: string, term: string, abbreviation: string, definition: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}, items: list<array{id: string, title: string, url: string}>, itemsCount: int, priority: array{rank: int, importance: \App\Enums\LearningImportance|null, sectionOrder: int, itemOrder: int}}
 * @phpstan-type GlossaryTermPayload array{id: string, term: string, abbreviation: string, definition: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}, items: list<array{id: string, title: string, url: string, inPath: bool, importance: \App\Enums\LearningImportance|null, state: string, section: array{id: string, title: string}|null, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: \App\Enums\DocumentStatus|null, title: string|null}|null}>}
 */
class LearningAdminPresenter
{
    public static function canSeeReps(User $user): bool
    {
        return $user->isSuperAdmin() || app(ResponsibilityResolver::class)->holdsAnywhere($user, ResponsibilityPurpose::StudentRepCoordination);
    }

    /** @return array{id: string, name: string} */
    public static function member(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name];
    }

    /** @return list<GlossaryPayload> */
    public static function glossary(string $locale, ?User $viewer = null): array
    {
        $viewer ??= auth()->user();
        $path = ResolveLearningPath::execute($viewer);
        $resolved = $path->knowledge->keyBy('item.id');
        $result = [];
        foreach (GlossaryTerm::query()->with(['knowledgeItems.section'])->get() as $term) {
            $linked = $term->knowledgeItems->map(fn (KnowledgeItem $item): array => self::priority($item, $resolved->get($item->id)))
                ->sortBy(fn (array $priority): array => [$priority['rank'], $priority['sectionOrder'], $priority['itemOrder']])->first()
                ?? ['rank' => 3, 'importance' => null, 'sectionOrder' => PHP_INT_MAX, 'itemOrder' => PHP_INT_MAX];
            $items = [];
            foreach ($term->knowledgeItems as $item) {
                $items[] = ['id' => $item->id, 'title' => (string) $item->getTranslation('title', $locale), 'url' => route('knowledgeItems.show', $item)];
            }
            $result[] = [...self::term($term, $locale), 'items' => $items, 'itemsCount' => count($items), 'priority' => $linked];
        }
        $collator = new \Collator($locale);
        usort($result, fn (array $a, array $b): int => [$a['priority']['rank'], $a['priority']['sectionOrder'], $a['priority']['itemOrder']]
            <=> [$b['priority']['rank'], $b['priority']['sectionOrder'], $b['priority']['itemOrder']]
            ?: (int) $collator->compare($a['term'], $b['term']));

        return $result;
    }

    /** @return GlossaryTermPayload */
    public static function glossaryTerm(GlossaryTerm $term, User $viewer, string $locale): array
    {
        $resolved = ResolveLearningPath::execute($viewer)->knowledge->keyBy('item.id');
        $items = [];
        foreach ($term->loadMissing(['knowledgeItems.section', 'knowledgeItems.sources.document.institution.tenant'])->knowledgeItems as $item) {
            $match = $resolved->get($item->id);
            $items[] = [
                'id' => $item->id, 'title' => (string) $item->getTranslation('title', $locale), 'url' => route('knowledgeItems.show', $item),
                'inPath' => $match !== null, 'importance' => $match?->importance, 'state' => $match?->state ?? 'pending',
                'section' => $item->section ? ['id' => $item->section->id, 'title' => (string) $item->section->getTranslation('title', $locale)] : null,
                'source' => KnowledgeBasePresenter::source($item, $viewer, $locale),
                'priority' => self::priority($item, $match),
            ];
        }
        usort($items, fn (array $a, array $b): int => [$a['priority']['rank'], $a['priority']['sectionOrder'], $a['priority']['itemOrder'], $a['id']]
            <=> [$b['priority']['rank'], $b['priority']['sectionOrder'], $b['priority']['itemOrder'], $b['id']]);
        foreach ($items as &$item) {
            unset($item['priority']);
        }

        return [...self::term($term, $locale), 'items' => $items];
    }

    /** @return array{rank: int, importance: LearningImportance|null, sectionOrder: int, itemOrder: int} */
    private static function priority(KnowledgeItem $item, ?ResolvedLearningItem $resolved): array
    {
        return ['rank' => $resolved === null ? 3 : (int) array_search($resolved->importance, LearningImportance::cases(), true),
            'importance' => $resolved?->importance, 'sectionOrder' => $item->section?->order ?? PHP_INT_MAX, 'itemOrder' => $item->order];
    }

    /** @return TermPayload */
    private static function term(GlossaryTerm $term, string $locale): array
    {
        return [
            'id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale),
            'abbreviation' => (string) $term->getTranslation('abbreviation', $locale), 'definition' => (string) $term->getTranslation('definition', $locale),
            'translations' => [
                'term' => ['lt' => (string) $term->getTranslation('term', 'lt', false), 'en' => (string) $term->getTranslation('term', 'en', false)],
                'abbreviation' => ['lt' => (string) $term->getTranslation('abbreviation', 'lt', false), 'en' => (string) $term->getTranslation('abbreviation', 'en', false)],
                'definition' => ['lt' => (string) $term->getTranslation('definition', 'lt', false), 'en' => (string) $term->getTranslation('definition', 'en', false)],
            ],
        ];
    }

    /** @return list<array{id: string, term: string, abbreviation: string}> */
    public static function glossaryOptions(string $locale): array
    {
        $options = [];
        foreach (GlossaryTerm::query()->get() as $term) {
            /** @var GlossaryTerm $term */
            $options[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale), 'abbreviation' => (string) $term->getTranslation('abbreviation', $locale)];
        }
        usort($options, fn (array $a, array $b): int => strcoll($a['term'], $b['term']));

        return $options;
    }

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{id: string, name: string, incoming: bool, lastActivity: string|null, institutions: list<array{id: string, title: string}>, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}}>
     */
    public static function representatives(Collection $users, string $locale): array
    {
        $paths = ResolveLearningPath::forUsers($users);
        $lastActivity = LearningProgress::query()->whereIn('user_id', $users->pluck('id'))
            ->select('user_id')->selectRaw('MAX(completed_at) as last_activity')->groupBy('user_id')->pluck('last_activity', 'user_id');
        $dutyIds = $users->flatMap(fn (User $user) => $user->authorization_duties->pluck('id'))->unique();
        $repDutyIds = Duty::query()->whereIn('id', $dutyIds)->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->pluck('id');
        $result = [];
        foreach ($users as $user) {
            $institutions = [];
            foreach ($user->authorization_duties->whereIn('id', $repDutyIds)->pluck('institution')->filter()->unique('id') as $institution) {
                $institutions[] = ['id' => $institution->id, 'title' => (string) $institution->getTranslation('name', $locale)];
            }
            $path = $paths->get($user->id);
            $result[] = ['id' => $user->id, 'name' => $user->name, 'incoming' => $path->currentStage === LearningStage::BeforeStart,
                'lastActivity' => $lastActivity->get($user->id), 'institutions' => $institutions, 'totals' => $path->totals()];
        }

        return $result;
    }

    /**
     * @param  list<array{id: string, name: string, incoming: bool, lastActivity: string|null, institutions: list<array{id: string, title: string}>, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}}>  $reps
     * @return array{representatives: int, essentialsFinished: int, notStarted: int, averageEssentialProgress: float, essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}
     */
    public static function summary(array $reps): array
    {
        $summary = ['representatives' => count($reps), 'essentialsFinished' => 0, 'notStarted' => 0, 'averageEssentialProgress' => 0.0,
            'essentialDone' => 0, 'essentialTotal' => 0, 'allDone' => 0, 'allTotal' => 0];
        $percentages = [];
        foreach ($reps as $rep) {
            $totals = $rep['totals'];
            $summary['notStarted'] += (int) ($totals['allDone'] === 0);
            if ($totals['essentialTotal'] > 0) {
                $summary['essentialsFinished'] += (int) ($totals['essentialDone'] === $totals['essentialTotal']);
                $percentages[] = 100 * $totals['essentialDone'] / $totals['essentialTotal'];
            }
            foreach ($rep['totals'] as $key => $value) {
                $summary[$key] += $value;
            }
        }
        $summary['averageEssentialProgress'] = $percentages === [] ? 0.0 : round(array_sum($percentages) / count($percentages), 1);

        return $summary;
    }
}
