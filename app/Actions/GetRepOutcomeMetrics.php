<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Task;
use App\Models\Type;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The rep outcome metrics (U25): did the loop get shorter? Computed from data the app already
 * stores — no tracking — as a monthly trend, so the same numbers back `metrics:reps` and the
 * Sistema page, and Phase 10.3 can re-run the baseline.
 *
 * Computed in PHP over small sets (a few hundred meetings a year) so it behaves the same on
 * MySQL and on SQLite. Months are by the meeting's date, or by the task's creation date.
 */
class GetRepOutcomeMetrics
{
    /** The Phase 0 snapshot (2026-09-17) and the target for each metric, in percent. */
    public const array TARGETS = [
        'recorded_within_week' => ['baseline' => 91.9, 'target' => 95.0],
        'vote_information' => ['baseline' => 35.1, 'target' => 50.0],
        'active_reps' => ['baseline' => 27.0, 'target' => 50.0],
        'task_completion' => ['baseline' => 67.2, 'target' => 80.0],
        'periodicity_gap' => ['baseline' => 44.9, 'target' => 65.0],
    ];

    private const string REP_TYPE_SLUG = 'studentu-atstovai';

    private const int ACTIVE_DAYS = 30;

    private const int RECORDED_WITHIN_DAYS = 7;

    /**
     * @return array{
     *     months: list<string>,
     *     meetings: array<string, array{total: int, withinWeek: int, items: int, itemsWithVotes: int, known: int, byRep: int}>,
     *     tasks: array<string, array{total: int, completed: int, medianDays: float|null, monthly: array<string, array{total: int, completed: int}>}>,
     *     activeReps: array{total: int, active: int},
     *     now: array<string, float|null>,
     *     targets: array<string, array{baseline: float, target: float}>
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
        $tasks = self::tasks($start, $monthKeys);
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
     * @return array<string, array{total: int, withinWeek: int, items: int, itemsWithVotes: int, known: int, byRep: int}>
     */
    private static function meetings(Carbon $start, Carbon $now, array $monthKeys): array
    {
        $rows = array_fill_keys($monthKeys, ['total' => 0, 'withinWeek' => 0, 'items' => 0, 'itemsWithVotes' => 0, 'known' => 0, 'byRep' => 0]);

        $meetings = Meeting::query()
            ->with('institutions:id')
            ->where('start_time', '>=', $start)
            ->where('start_time', '<', $now)
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
    private static function agendaItems(Collection $meetingIds): Collection
    {
        /** @var Collection<int, AgendaItem> $items */
        $items = new Collection;

        foreach ($meetingIds->chunk(500) as $chunk) {
            $items = $items->concat(
                AgendaItem::query()
                    ->whereIn('meeting_id', $chunk->all())
                    ->withCount(['votes as votes_with_information_count' => fn ($votes) => $votes
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
    private static function creators(Collection $meetingIds): array
    {
        $creators = [];

        foreach ($meetingIds->chunk(500) as $chunk) {
            Activity::query()
                ->where('subject_type', MorphMap::alias(Meeting::class))
                ->where('event', 'created')
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
        $typeId = Type::query()->where('slug', self::REP_TYPE_SLUG)->value('id');

        if ($typeId === null) {
            return [];
        }

        return Dutiable::query()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereHas('duty.types', fn ($types) => $types->where('types.id', $typeId))
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
     * @return array<string, array{total: int, completed: int, medianDays: float|null, monthly: array<string, array{total: int, completed: int}>}>
     */
    private static function tasks(Carbon $start, array $monthKeys): array
    {
        $byType = [];

        Task::query()
            ->where('created_at', '>=', $start)
            ->toBase()
            ->get(['action_type', 'created_at', 'completed_at'])
            ->each(function (object $task) use (&$byType, $monthKeys): void {
                $type = $task->action_type ?? 'manual';
                $created = Carbon::parse($task->created_at);

                $byType[$type] ??= ['total' => 0, 'completed' => 0, 'days' => [], 'monthly' => array_fill_keys($monthKeys, ['total' => 0, 'completed' => 0])];
                $byType[$type]['total']++;
                $byType[$type]['monthly'][$created->format('Y-m')]['total']++;

                if ($task->completed_at !== null) {
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
    private static function activeReps(Carbon $now): array
    {
        $typeId = Type::query()->where('slug', self::REP_TYPE_SLUG)->value('id');

        if ($typeId === null) {
            return ['total' => 0, 'active' => 0];
        }

        $reps = fn () => User::query()->whereHas('dutiables', fn ($terms) => $terms
            ->activeOn()
            ->whereHas('duty.types', fn ($types) => $types->where('types.id', $typeId)));

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
