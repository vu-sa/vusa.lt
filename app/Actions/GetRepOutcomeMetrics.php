<?php

namespace App\Actions;

use App\Enums\ResponsibilityPurpose;
use App\Models\Activity;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\MeetingCompletionService;
use App\Services\RepStatisticsScope;
use App\Support\MorphMap;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Measures representation outcomes with entity contributions for deduplicated tenant comparisons. */
class GetRepOutcomeMetrics
{
    /** Targets for the legacy monthly report, retained for archived calculations. */
    public const array TARGETS = [
        'recorded_within_week' => 95.0,
        'vote_information' => 50.0,
        'active_reps' => 50.0,
        'task_completion' => 80.0,
        'periodicity_gap' => 65.0,
    ];

    /** Every metric a window measures, in display order. */
    public const array METRICS = ['recorded_within_week', 'vote_information', 'task_completion', 'periodicity_gap', 'active_reps', 'own_recording'];

    /** A snapshot measures the days before it, so a daily series moves smoothly and stays comparable. */
    public const int SNAPSHOT_WINDOW_DAYS = 30;

    private const int ACTIVE_DAYS = 30;

    private const int RECORDED_WITHIN_DAYS = 7;

    /**
     * @return array{
     *     months: list<string>,
     *     meetings: array<string, array{total: int, withinWeek: int, items: int, itemsWithVotes: int, known: int, byRep: int}>,
     *     tasks: array<string, array{total: int, completed: int, medianDays: float|null, monthly: array<string, array{total: int, completed: int}>}>,
     *     activeReps: array{total: int, active: int},
     *     now: array<string, float|null>,
     *     targets: array<string, float>
     * }
     */
    public static function execute(int $months = 12): array
    {
        $now = now();
        $start = $now->copy()->startOfMonth()->subMonths(max($months, 1) - 1);

        $monthKeys = [];
        for ($month = $start->copy(); $month->lessThanOrEqualTo($now); $month->addMonth()) {
            $monthKeys[] = $month->format('Y-m');
        }

        $meetings = self::meetings($start, $now, $monthKeys);
        $tasks = self::tasks($start, $now, $monthKeys);
        $activeReps = self::activeReps($now);

        return [
            'months' => $monthKeys,
            'meetings' => $meetings,
            'tasks' => $tasks,
            'activeReps' => $activeReps,
            'now' => self::summarize($meetings, $tasks, $activeReps),
            'targets' => self::TARGETS,
        ];
    }

    /**
     * Every metric over [$from, $to), with the counts behind each rate. `active_reps` has no
     * history (users.last_action is a current value), so it is measured only when asked.
     * `$tenantIds` narrows every metric to those padaliniai; null measures the whole VU SA.
     *
     * @param  list<int>|null  $tenantIds
     * @return array<string, array{value: float|null, part: int, whole: int}>
     */
    public static function forWindow(CarbonInterface $from, CarbonInterface $to, bool $withActiveReps = true, ?array $tenantIds = null): array
    {
        return self::measure($from, $to, $withActiveReps, $tenantIds)['metrics'];
    }

    public static function measure(CarbonInterface $from, CarbonInterface $to, bool $withActiveReps = true, ?array $tenantIds = null): array
    {
        $institutionIds = RepStatisticsScope::institutions($tenantIds)->pluck('institutions.id');
        $meetings = self::knownMeetings($institutionIds, $from, $to);
        [$meetingContributors, $delays] = self::meetingContributions($meetings, $to);
        $contributors = [
            ...$meetingContributors,
            ...self::taskContributions($tenantIds, $from, $to),
            ...self::representativeContributions($institutionIds, $tenantIds, $to, $withActiveReps),
        ];
        $metrics = [];
        foreach (self::METRICS as $key) {
            if ($key === 'active_reps' && ! $withActiveReps) {
                continue;
            }
            $metrics[$key] = self::countContribution($contributors[$key] ?? ['part' => [], 'whole' => []]);
        }

        return ['metrics' => $metrics, 'contributors' => $contributors, 'supplementary' => [
            'recording_delays' => $delays, 'requests' => self::requestStates($institutionIds, $from, $to),
        ]];
    }

    /** Retrospective cohorts use all records available now, including late registrations. */
    public static function forPeriod(CarbonInterface $from, CarbonInterface $to, ?array $tenantIds = null): array
    {
        $institutions = RepStatisticsScope::institutions($tenantIds)->pluck('institutions.id');
        $meetings = Meeting::query()->with(['institutions.types', 'agendaItems.votes'])
            ->whereHas('institutions', fn ($query) => $query->whereIn('institutions.id', $institutions))
            ->where('start_time', '>=', $from)->where('start_time', '<', $to)
            ->where('created_at', '<=', now())
            ->get(['id', 'start_time', 'created_at']);
        $completion = app(MeetingCompletionService::class);
        $items = $meetings->flatMap(fn (Meeting $meeting) => $meeting->agendaItems);
        $timely = $meetings->filter(fn ($meeting) => $meeting->created_at->lte($meeting->start_time->copy()->addDays(7)))->count();
        $completeItems = $meetings->sum(function (Meeting $meeting) use ($completion): int {
            $requiresStudentPerspective = $meeting->requiresStudentPerspective();

            return $meeting->agendaItems->filter(fn (AgendaItem $item): bool => $completion->itemIsComplete($item, $requiresStudentPerspective))->count();
        });
        $tasks = self::taskContributions($tenantIds, $from, $to, now());

        return [
            'recorded_within_week' => ['value' => self::rate($timely, $meetings->count()), 'part' => $timely, 'whole' => $meetings->count()],
            'agenda_completion' => ['value' => self::rate($completeItems, $items->count()), 'part' => $completeItems, 'whole' => $items->count()],
            'task_completion' => self::countContribution($tasks['task_completion'] ?? ['part' => [], 'whole' => []]),
        ];
    }

    private static function knownMeetings(Collection $institutionIds, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Meeting::withTrashed()->with('institutions:id')
            ->whereHas('institutions', fn ($query) => $query->whereIn('institutions.id', $institutionIds))
            ->where('start_time', '>=', $from)->where('start_time', '<', $to)
            ->where('created_at', '<=', $to)
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('deleted_at', '>', $to))
            ->get(['id', 'start_time', 'created_at']);
    }

    private static function addContribution(array &$contributors, string $key, string $id, bool $part): void
    {
        $contributors[$key] ??= ['part' => [], 'whole' => []];
        $contributors[$key]['whole'][] = $id;
        if ($part) {
            $contributors[$key]['part'][] = $id;
        }
    }

    private static function meetingContributions(Collection $meetings, CarbonInterface $to): array
    {
        $contributors = $delays = [];
        foreach ($meetings as $meeting) {
            self::addContribution($contributors, 'recorded_within_week', (string) $meeting->id, $meeting->created_at->lte($meeting->start_time->copy()->addDays(7)));
            $delays[(string) $meeting->id] = max(0, $meeting->start_time->diffInSeconds($meeting->created_at) / 86400);
        }
        foreach (self::agendaItems($meetings->pluck('id'), $to) as $item) {
            self::addContribution($contributors, 'vote_information', (string) $item->id, $item->getAttribute('votes_with_information_count') > 0);
        }
        $terms = self::representativeTerms();
        $meetingsById = $meetings->keyBy('id');
        foreach (self::creators($meetings->pluck('id'), $to) as $meetingId => $creatorId) {
            self::addContribution($contributors, 'own_recording', $meetingId, self::isRepresentativeAt($creatorId, $meetingsById[$meetingId], $terms));
        }

        return [$contributors, $delays];
    }

    private static function taskContributions(?array $tenantIds, CarbonInterface $from, CarbonInterface $to, ?CarbonInterface $observedAt = null): array
    {
        $contributors = [];
        $tasks = RepStatisticsScope::tasks($tenantIds)->where('created_at', '>=', $from)->where('created_at', '<', $to)
            ->get(['id', 'action_type', 'completed_at']);
        foreach ($tasks as $task) {
            $complete = $task->completed_at !== null && $task->completed_at->lte($observedAt ?? $to);
            self::addContribution($contributors, 'task_completion', (string) $task->id, $complete);
            if ($task->action_type?->value === 'periodicity_gap') {
                self::addContribution($contributors, 'periodicity_gap', (string) $task->id, $complete);
            }
        }

        return $contributors;
    }

    private static function representativeContributions(Collection $institutionIds, ?array $tenantIds, CarbonInterface $to, bool $withActiveReps): array
    {
        $contributors = [];
        if ($withActiveReps) {
            foreach (RepStatisticsScope::representatives($tenantIds, $to)->get(['id', 'last_action']) as $rep) {
                $active = $rep->last_action !== null && $rep->last_action->gte($to->copy()->subDays(30)) && $rep->last_action->lte($to);
                self::addContribution($contributors, 'active_reps', (string) $rep->id, $active);
            }
        }
        $represented = Dutiable::query()->activeOn($to->toDateString())->where('dutiable_type', MorphMap::alias(User::class))
            ->whereHas('duty', fn ($duty) => $duty->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->whereIn('institution_id', $institutionIds))
            ->with('duty:id,institution_id')->get()->pluck('duty.institution_id')->unique()->all();
        foreach ($institutionIds as $id) {
            self::addContribution($contributors, 'representation_coverage', (string) $id, in_array($id, $represented, true));
        }

        return $contributors;
    }

    private static function requestStates(Collection $institutionIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $requests = [];
        foreach (InstitutionActivityRequest::query()->whereIn('institution_id', $institutionIds)
            ->where('created_at', '>=', $from)->where('created_at', '<', $to)->get() as $request) {
            $key = $request->send_id.':'.$request->institution_id;
            $state = match (true) {
                $request->answered_at !== null && $request->answered_at->lte($to) => 'answered',
                $request->resolved_at !== null && $request->resolved_at->lte($to) => 'resolved',
                $request->expires_at->lte($to) => 'expired',
                default => 'pending',
            };
            $priority = ['pending' => 0, 'expired' => 1, 'resolved' => 2, 'answered' => 3];
            if (! isset($requests[$key]) || $priority[$state] > $priority[$requests[$key]]) {
                $requests[$key] = $state;
            }
        }

        return $requests;
    }

    public static function countContribution(array $contribution): array
    {
        $part = count(array_unique($contribution['part']));
        $whole = count(array_unique($contribution['whole']));

        return ['value' => self::rate($part, $whole), 'part' => $part, 'whole' => $whole];
    }

    /**
     * Each metric over the whole window, keyed like `TARGETS`.
     *
     * @param  array<string, array{total: int, withinWeek: int, items: int, itemsWithVotes: int, known: int, byRep: int}>  $meetings
     * @param  array<string, array{total: int, completed: int, medianDays: float|null, monthly: array<string, array{total: int, completed: int}>}>  $tasks
     * @param  array{total: int, active: int}  $activeReps
     * @return array<string, float|null>
     */
    private static function summarize(array $meetings, array $tasks, array $activeReps): array
    {
        $sum = fn (string $key): int => array_sum(array_column($meetings, $key));
        $gap = $tasks['periodicity_gap'] ?? ['total' => 0, 'completed' => 0];

        return [
            'recorded_within_week' => self::rate($sum('withinWeek'), $sum('total')),
            'vote_information' => self::rate($sum('itemsWithVotes'), $sum('items')),
            'active_reps' => self::rate($activeReps['active'], $activeReps['total']),
            'task_completion' => self::rate(array_sum(array_column($tasks, 'completed')), array_sum(array_column($tasks, 'total'))),
            'periodicity_gap' => self::rate($gap['completed'], $gap['total']),
        ];
    }

    /** A share in percent to one decimal, or null when there was nothing to measure. */
    public static function rate(int $part, int $whole): ?float
    {
        return $whole === 0 ? null : round($part / $whole * 100, 1);
    }

    /**
     * @param  list<string>  $monthKeys
     * @param  list<int>|null  $tenantIds
     * @return array<string, array{total: int, withinWeek: int, items: int, itemsWithVotes: int, known: int, byRep: int}>
     */
    private static function meetings(Carbon $start, Carbon $now, array $monthKeys, ?array $tenantIds = null): array
    {
        $rows = array_fill_keys($monthKeys, ['total' => 0, 'withinWeek' => 0, 'items' => 0, 'itemsWithVotes' => 0, 'known' => 0, 'byRep' => 0]);

        $meetings = Meeting::query()
            ->with('institutions:id')
            ->whereHas('institutions', fn ($institutions) => $institutions->whereIn('institutions.id', RepStatisticsScope::institutions($tenantIds)->select('institutions.id')))
            ->where('start_time', '>=', $start)
            ->where('start_time', '<', $now)
            ->where('created_at', '<=', $now)
            ->get(['id', 'start_time', 'created_at']);

        if ($meetings->isEmpty()) {
            return $rows;
        }

        $byId = $meetings->keyBy(fn (Meeting $meeting): string => (string) $meeting->id);
        $monthOf = $meetings->mapWithKeys(fn (Meeting $meeting): array => [(string) $meeting->id => $meeting->start_time->format('Y-m')]);

        foreach ($meetings as $meeting) {
            $month = $monthOf[(string) $meeting->id];
            $rows[$month]['total']++;

            if ($meeting->created_at->lessThanOrEqualTo($meeting->start_time->copy()->addDays(self::RECORDED_WITHIN_DAYS))) {
                $rows[$month]['withinWeek']++;
            }
        }

        foreach (self::agendaItems($meetings->pluck('id')) as $item) {
            $month = $monthOf[(string) $item->meeting_id];
            $rows[$month]['items']++;

            if ((int) $item->getAttribute('votes_with_information_count') > 0) {
                $rows[$month]['itemsWithVotes']++;
            }
        }

        $repTerms = self::representativeTerms();

        foreach (self::creators($meetings->pluck('id')) as $meetingId => $creatorId) {
            $meeting = $byId[(string) $meetingId];
            $month = $monthOf[(string) $meetingId];
            $rows[$month]['known']++;

            if (self::isRepresentativeAt($creatorId, $meeting, $repTerms)) {
                $rows[$month]['byRep']++;
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, mixed>  $meetingIds
     * @return Collection<int, AgendaItem>
     */
    private static function agendaItems(Collection $meetingIds, ?CarbonInterface $to = null): Collection
    {
        /** @var Collection<int, AgendaItem> $items */
        $items = new Collection;

        foreach ($meetingIds->chunk(500) as $chunk) {
            $items = $items->concat(
                AgendaItem::query()
                    ->whereIn('meeting_id', $chunk->all())
                    ->when($to !== null, fn ($query) => $query->where('created_at', '<=', $to))
                    ->withCount(['votes as votes_with_information_count' => fn ($votes) => $votes
                        ->when($to !== null, fn ($query) => $query->where('created_at', '<=', $to)->where('updated_at', '<=', $to))
                        ->where(fn ($vote) => $vote
                            ->where(fn ($decision) => $decision->whereNotNull('decision')->where('decision', '!=', ''))
                            ->orWhere(fn ($studentVote) => $studentVote->whereNotNull('student_vote')->where('student_vote', '!=', '')))])
                    ->get(['id', 'meeting_id'])
            );
        }

        return $items;
    }

    /**
     * Who recorded each meeting, from the activity log. Meetings the log no longer covers (it is
     * kept for 365 days) or that were made by a seeder or a job have no person and drop out.
     *
     * @param  Collection<int, mixed>  $meetingIds
     * @return array<string, string>
     */
    private static function creators(Collection $meetingIds, ?CarbonInterface $to = null): array
    {
        $creators = [];

        foreach ($meetingIds->chunk(500) as $chunk) {
            Activity::query()
                ->where('subject_type', MorphMap::alias(Meeting::class))
                ->where('event', 'created')
                ->when($to !== null, fn ($query) => $query->where('created_at', '<=', $to))
                ->where('causer_type', MorphMap::alias(User::class))
                ->whereIn('subject_id', $chunk->map(fn ($id): string => (string) $id)->all())
                ->get(['subject_id', 'causer_id'])
                ->each(function (Activity $activity) use (&$creators): void {
                    $creators[(string) $activity->subject_id] = (string) $activity->causer_id;
                });
        }

        return $creators;
    }

    /**
     * Every student-representative term, by institution.
     *
     * @return array<array-key, list<Dutiable>>
     */
    private static function representativeTerms(): array
    {
        return Dutiable::query()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereHas('duty', fn ($duty) => $duty->holdingPurpose(ResponsibilityPurpose::StudentRepresentation))
            ->with('duty:id,institution_id')
            ->get(['id', 'duty_id', 'dutiable_id', 'start_date', 'end_date'])
            ->groupBy(fn (Dutiable $term): string => (string) $term->duty->institution_id)
            ->map(fn ($terms): array => $terms->values()->all())
            ->all();
    }

    /**
     * @param  array<array-key, list<Dutiable>>  $repTerms
     */
    private static function isRepresentativeAt(string $userId, Meeting $meeting, array $repTerms): bool
    {
        $day = $meeting->start_time->copy()->startOfDay();

        foreach ($meeting->institutions as $institution) {
            $held = array_filter(
                $repTerms[(string) $institution->id] ?? [],
                fn (Dutiable $term): bool => (string) $term->dutiable_id === $userId
                    && $term->start_date->copy()->startOfDay()->lessThanOrEqualTo($day)
                    && ($term->end_date === null || $term->end_date->copy()->endOfDay()->greaterThanOrEqualTo($day)),
            ) !== [];

            if ($held) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $monthKeys
     * @param  list<int>|null  $tenantIds
     * @return array<string, array{total: int, completed: int, medianDays: float|null, monthly: array<string, array{total: int, completed: int}>}>
     */
    private static function tasks(Carbon $start, Carbon $end, array $monthKeys, ?array $tenantIds = null): array
    {
        $byType = [];

        RepStatisticsScope::tasks($tenantIds)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->toBase()
            ->get(['action_type', 'created_at', 'completed_at'])
            ->each(function (object $task) use (&$byType, $monthKeys, $end): void {
                $type = $task->action_type ?? 'manual';
                $created = Carbon::parse($task->created_at);

                $byType[$type] ??= ['total' => 0, 'completed' => 0, 'days' => [], 'monthly' => array_fill_keys($monthKeys, ['total' => 0, 'completed' => 0])];
                $byType[$type]['total']++;
                $byType[$type]['monthly'][$created->format('Y-m')]['total']++;

                if ($task->completed_at !== null && Carbon::parse($task->completed_at)->lte($end)) {
                    $byType[$type]['completed']++;
                    $byType[$type]['monthly'][$created->format('Y-m')]['completed']++;
                    $byType[$type]['days'][] = abs($created->diffInSeconds(Carbon::parse($task->completed_at))) / 86400;
                }
            });

        ksort($byType);

        return array_map(fn (array $type): array => [
            'total' => $type['total'],
            'completed' => $type['completed'],
            'medianDays' => self::median($type['days']),
            'monthly' => $type['monthly'],
        ], $byType);
    }

    /**
     * `users.last_action` is a current value with no history, so this is a snapshot, not a trend.
     *
     * @return array{total: int, active: int}
     */
    private static function activeReps(Carbon $now, ?array $tenantIds = null): array
    {
        $reps = fn () => RepStatisticsScope::representatives($tenantIds, $now);

        return [
            'total' => $reps()->count(),
            'active' => $reps()->where('last_action', '>=', $now->copy()->subDays(self::ACTIVE_DAYS))->count(),
        ];
    }

    /**
     * @param  list<float>  $values
     */
    private static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        $median = count($values) % 2 === 1 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;

        return round($median, 1);
    }
}
