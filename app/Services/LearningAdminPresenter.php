<?php

namespace App\Services;

use App\Actions\ResolveLearningPath;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\LearningStepProgress;
use App\Models\User;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Support\Collection;

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

    /** @return list<array{id: string, term: string, definition: string, stepsCount: int, steps: list<array{id: string, title: string, url: string|null, isDocument: bool}>, abbreviation: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}}> */
    public static function glossary(string $locale, ?User $viewer = null): array
    {
        $result = [];
        foreach (GlossaryTerm::query()->with(['steps.sources.document.institution.tenant'])->withCount('steps')->get() as $term) {
            /** @var GlossaryTerm $term */
            $steps = [];
            foreach ($term->steps as $step) {
                $source = LearningPathPresenter::source($step, $viewer ?? auth()->user(), $locale);
                $definition = $step->is_system ? (SystemStepRegistry::definitions()[$step->key] ?? null) : null;
                $steps[] = [
                    'id' => $step->id,
                    'title' => $definition ? (string) __($definition['title'], [], $locale) : (string) $step->getTranslation('title', $locale),
                    'url' => ($step->kind === LearningStepKind::Knowledge ? $source['url'] ?? null : null) ?? route('learningSteps.show', $step),
                    'isDocument' => $step->kind === LearningStepKind::Knowledge,
                ];
            }
            $result[] = [
                'id' => $term->id,
                'term' => (string) $term->getTranslation('term', $locale),
                'abbreviation' => (string) $term->getTranslation('abbreviation', $locale),
                'definition' => (string) $term->getTranslation('definition', $locale),
                'stepsCount' => (int) $term->steps_count,
                'steps' => $steps,
                'translations' => [
                    'term' => [
                        'lt' => (string) $term->getTranslation('term', 'lt', false),
                        'en' => (string) $term->getTranslation('term', 'en', false),
                    ],
                    'abbreviation' => [
                        'lt' => (string) $term->getTranslation('abbreviation', 'lt', false),
                        'en' => (string) $term->getTranslation('abbreviation', 'en', false),
                    ],
                    'definition' => [
                        'lt' => (string) $term->getTranslation('definition', 'lt', false),
                        'en' => (string) $term->getTranslation('definition', 'en', false),
                    ],
                ],
            ];
        }

        return $result;
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
        $lastActivity = LearningStepProgress::query()->whereIn('user_id', $users->pluck('id'))
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
