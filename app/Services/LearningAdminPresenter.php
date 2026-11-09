<?php

namespace App\Services;

use App\Actions\GetReceivedHandoverNotes;
use App\Actions\ResolveLearningPath;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\HandoverNote;
use App\Models\LearningStepProgress;
use App\Models\User;
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

    /** @return list<array{id: string, term: string, definition: string, stepsCount: int, translations: array{term: array{lt: string, en: string}, definition: array{lt: string, en: string}}}> */
    public static function glossary(string $locale): array
    {
        $result = [];
        foreach (GlossaryTerm::query()->withCount('steps')->get() as $term) {
            /** @var GlossaryTerm $term */
            $result[] = [
                'id' => $term->id,
                'term' => (string) $term->getTranslation('term', $locale),
                'definition' => (string) $term->getTranslation('definition', $locale),
                'stepsCount' => (int) $term->steps_count,
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

    /** @return array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null} */
    public static function note(HandoverNote $note, string $locale): array
    {
        return [
            'id' => $note->id, 'dutyId' => $note->duty_id, 'duty' => (string) $note->duty->getTranslation('name', $locale),
            'author' => ['id' => $note->author->id, 'name' => $note->author->name], 'body' => $note->body,
            'createdAt' => $note->created_at?->toIso8601String(), 'updatedAt' => $note->updated_at?->toIso8601String(),
        ];
    }

    /** @return list<array{dutyId: string, title: string, note: array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null}|null}> */
    public static function writable(User $user, string $locale): array
    {
        $result = [];
        $duties = $user->duties()->wherePivot('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('dutiables.end_date')->orWhere('dutiables.end_date', '>=', today()->subDays(60)))
            ->orderByDesc('dutiables.start_date')
            ->get()->unique('id')->values();
        $notes = HandoverNote::query()->where('user_id', $user->id)->whereIn('duty_id', $duties->pluck('id'))->with(['duty', 'author'])->latest()->get();
        foreach ($duties as $duty) {
            if ($user->can('create', [HandoverNote::class, $duty])) {
                $note = $notes->first(fn (HandoverNote $note): bool => $note->duty_id === $duty->id && $note->dutiable_id === $duty->pivot->id);
                $result[] = ['dutyId' => $duty->id, 'title' => (string) $duty->getTranslation('name', $locale), 'note' => $note ? self::note($note, $locale) : null];
            }
        }

        return $result;
    }

    /** @return list<array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null}> */
    public static function received(User $user, string $locale): array
    {
        $result = [];
        foreach (GetReceivedHandoverNotes::execute($user) as $note) {
            $result[] = self::note($note, $locale);
        }

        return $result;
    }

    /** @return list<array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null}> */
    public static function representativeNotes(User $rep, User $viewer, string $locale): array
    {
        $received = GetReceivedHandoverNotes::execute($rep);
        $written = HandoverNote::query()->where('user_id', $rep->id)
            ->whereIn('duty_id', $rep->authorization_duties->pluck('id'))
            ->with(['author', 'duty.institution'])->get();
        $result = [];
        foreach ($received->concat($written)->unique('id')->sortByDesc('updated_at') as $note) {
            if ($viewer->can('view', $note)) {
                $result[] = self::note($note, $locale);
            }
        }

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
