<?php

namespace App\Services;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Actions\GetReceivedHandoverNotes;
use App\Actions\ResolveLearningPath;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\HandoverNote;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Models\User;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LearningAdminPresenter
{
    /** @return array{canSeeReps: bool, canManage: bool} */
    public static function navigation(User $user): array
    {
        return ['canSeeReps' => self::canSeeReps($user), 'canManage' => $user->can('manage', LearningStep::class)];
    }

    public static function canSeeReps(User $user): bool
    {
        return $user->isSuperAdmin() || app(ResponsibilityResolver::class)->holdsAnywhere($user, ResponsibilityPurpose::StudentRepCoordination);
    }

    /** @return array{id: string, name: string} */
    public static function member(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name];
    }

    /** @return list<array{id: string, term: string, definition: string, stepsCount: int, steps: list<array{id: string, title: string, url: string|null, isDocument: bool}>, translations: array{term: array{lt: string, en: string}, definition: array{lt: string, en: string}}}> */
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
                'definition' => (string) $term->getTranslation('definition', $locale),
                'stepsCount' => (int) $term->steps_count,
                'steps' => $steps,
                'translations' => [
                    'term' => [
                        'lt' => (string) $term->getTranslation('term', 'lt', false),
                        'en' => (string) $term->getTranslation('term', 'en', false),
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

    /** @return list<array{id: string, term: string}> */
    public static function glossaryOptions(string $locale): array
    {
        $options = [];
        foreach (GlossaryTerm::query()->get() as $term) {
            /** @var GlossaryTerm $term */
            $options[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale)];
        }
        usort($options, fn (array $a, array $b): int => strcoll($a['term'], $b['term']));

        return $options;
    }

    /** @return array{id: string, dutyId: string, duty: string, seatDutyId: string, seatTitle: string, cadenceId: string|null, cadenceLabel: string, termStart: string|null, author: array{id: string, name: string}, situation: string|null, open_issues: string|null, people: string|null, resources: string|null, advice: string|null, createdAt: string|null, updatedAt: string|null} */
    public static function note(HandoverNote $note, string $locale, ?Duty $seat = null): array
    {
        $seat ??= $note->duty;
        $start = $note->cadence->start_date ?? $note->dutiable->start_date ?? $note->created_at;
        $end = $note->cadence->end_date ?? $note->dutiable->end_date ?? $start;

        return [
            'id' => $note->id, 'dutyId' => $note->duty_id, 'duty' => (string) $note->duty->getTranslation('name', $locale),
            'seatDutyId' => $seat->id, 'seatTitle' => (string) $seat->getTranslation('name', $locale),
            'cadenceId' => $note->cadence_id,
            'cadenceLabel' => $note->cadence !== null ? (string) $note->cadence->label : self::termYears($start, $end),
            'termStart' => $start?->toDateString(),
            'author' => self::member($note->author),
            'situation' => $note->situation, 'open_issues' => $note->open_issues, 'people' => $note->people,
            'resources' => $note->resources, 'advice' => $note->advice,
            'createdAt' => $note->created_at?->toIso8601String(), 'updatedAt' => $note->updated_at?->toIso8601String(),
        ];
    }

    private static function termYears(?Carbon $start, ?Carbon $end): string
    {
        return implode('–', array_unique(array_filter([$start?->year, $end?->year])));
    }

    /** @return list<array{dutyId: string, title: string, cadenceId: string|null, cadenceLabel: string, termStart: string, author: array{id: string, name: string}, note: array{id: string, dutyId: string, duty: string, seatDutyId: string, seatTitle: string, cadenceId: string|null, cadenceLabel: string, termStart: string|null, author: array{id: string, name: string}, situation: string|null, open_issues: string|null, people: string|null, resources: string|null, advice: string|null, createdAt: string|null, updatedAt: string|null}|null, notes: list<array{id: string, dutyId: string, duty: string, seatDutyId: string, seatTitle: string, cadenceId: string|null, cadenceLabel: string, termStart: string|null, author: array{id: string, name: string}, situation: string|null, open_issues: string|null, people: string|null, resources: string|null, advice: string|null, createdAt: string|null, updatedAt: string|null}>}> */
    public static function writable(User $user, string $locale): array
    {
        $result = [];
        $duties = $user->duties()->wherePivot('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('dutiables.end_date')->orWhereDate('dutiables.end_date', '>=', today()->subDays(60)))
            ->with(['predecessors' => fn ($query) => $query->withTrashed()])
            ->orderByDesc('dutiables.start_date')->get()->unique('id')->values();
        $ids = $duties->flatMap(fn (Duty $duty) => $duty->predecessors->pluck('id')->push($duty->id))->unique();
        $notes = HandoverNote::query()->whereIn('duty_id', $ids)->with(['duty.institution', 'author', 'cadence', 'dutiable'])->get();
        foreach ($duties as $duty) {
            if (! $user->can('create', [HandoverNote::class, $duty])) {
                continue;
            }
            $cadence = ResolveCadenceForDuty::execute($duty, $duty->pivot->start_date);
            $note = $notes->first(fn (HandoverNote $note): bool => $note->duty_id === $duty->id
                && $note->user_id === $user->id && $note->cadence_id === $cadence?->id);
            $visibleIds = $duty->predecessors->pluck('id')->push($duty->id);
            $history = [];
            foreach ($notes->whereIn('duty_id', $visibleIds) as $entry) {
                if ($user->can('view', $entry)) {
                    $history[] = self::note($entry, $locale, $duty);
                }
            }
            usort($history, fn (array $a, array $b): int => strcmp($b['termStart'] ?? '', $a['termStart'] ?? '') ?: strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));
            $result[] = [
                'dutyId' => $duty->id, 'title' => (string) $duty->getTranslation('name', $locale),
                'cadenceId' => $cadence?->id,
                'cadenceLabel' => $cadence !== null ? (string) $cadence->label : self::termYears($duty->pivot->start_date, $duty->pivot->end_date ?? $duty->pivot->start_date),
                'termStart' => ($cadence->start_date ?? $duty->pivot->start_date)->toDateString(),
                'author' => self::member($user), 'note' => $note ? self::note($note, $locale, $duty) : null, 'notes' => $history,
            ];
        }

        return $result;
    }

    /** @return list<array{id: string, dutyId: string, duty: string, seatDutyId: string, seatTitle: string, cadenceId: string|null, cadenceLabel: string, termStart: string|null, author: array{id: string, name: string}, situation: string|null, open_issues: string|null, people: string|null, resources: string|null, advice: string|null, createdAt: string|null, updatedAt: string|null}> */
    public static function received(User $user, string $locale): array
    {
        $result = [];
        foreach (GetReceivedHandoverNotes::execute($user) as $note) {
            foreach ($user->duties->unique('id') as $seat) {
                if (($seat->id === $note->duty_id || $seat->predecessors->contains('id', $note->duty_id)) && $user->can('view', $note)) {
                    $result[] = self::note($note, $locale, $seat);
                }
            }
        }

        return $result;
    }

    /** @return list<array{id: string, dutyId: string, duty: string, seatDutyId: string, seatTitle: string, cadenceId: string|null, cadenceLabel: string, termStart: string|null, author: array{id: string, name: string}, situation: string|null, open_issues: string|null, people: string|null, resources: string|null, advice: string|null, createdAt: string|null, updatedAt: string|null}> */
    public static function representativeNotes(User $rep, User $viewer, string $locale): array
    {
        $received = GetReceivedHandoverNotes::execute($rep);
        $written = HandoverNote::query()->where('user_id', $rep->id)
            ->whereIn('duty_id', $rep->duties->pluck('id'))
            ->with(['author', 'duty.institution', 'cadence', 'dutiable'])->get();
        $result = [];
        foreach ($received->concat($written)->unique('id') as $note) {
            if (! $viewer->can('view', $note)) {
                continue;
            }
            foreach ($rep->duties->unique('id') as $seat) {
                if ($seat->id === $note->duty_id || $seat->predecessors->contains('id', $note->duty_id)) {
                    $result[] = self::note($note, $locale, $seat);
                }
            }
        }
        usort($result, fn (array $a, array $b): int => strcmp($b['termStart'] ?? '', $a['termStart'] ?? '') ?: strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));

        return $result;
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
