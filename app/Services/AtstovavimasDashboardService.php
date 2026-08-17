<?php

namespace App\Services;

use App\Enums\InstitutionActivityStatus;
use App\Enums\TenantType;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Services\ResourceServices\DutyService;
use App\Settings\MeetingSettings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AtstovavimasDashboardService
{
    private const int REPRESENTATIVE_PREVIEW_LIMIT = 4;

    public function __construct(
        private readonly InstitutionActivityStatusService $activityStatusService,
        private readonly AcademicCalendarService $academicCalendar,
        private readonly ModelAuthorizer $authorizer,
        private readonly MeetingSettings $meetingSettings,
    ) {}

    /**
     * Tenant timeline for the ViSAK dashboard: direct institutions of the given
     * tenants only (relationships are intentionally not resolved — the tenant
     * Gantt shows each tenant's own institutions), with server-computed activity
     * statuses and summaries. Meetings are served separately via tenantMeetings().
     *
     * @param  list<int>  $tenantIds
     * @return array{
     *   institutions: list<array<string, mixed>>,
     *   institution_summary: array<string, int>,
     *   representative_activity: array<string, mixed>
     * }
     */
    public function tenantTimeline(array $tenantIds): array
    {
        $institutions = DutyService::getTimelineInstitutionsForTenants($tenantIds, $this->authorizer);
        $institutions = $this->withoutExcludedInstitutionTypes($institutions);
        $this->decorateInstitutions($institutions);

        return [
            'institutions' => $institutions
                ->map(fn (Institution $institution) => $this->mapInstitution($institution))
                ->values()
                ->all(),
            'institution_summary' => $this->institutionSummary($institutions),
            'representative_activity' => $this->representativeActivitySummary($tenantIds),
        ];
    }

    /**
     * Daily institution status breakdown for the trailing $days days, so the
     * tenant dashboard can chart how health has changed over time.
     *
     * Institutions with no meetings and no check-ins at all are excluded from the
     * sweep below: InstitutionActivityStatusService::resolve() always puts them in
     * the NoActivity branch (nextMeeting/activeCheckIn/lastActivityAt are all
     * necessarily null) for every possible date — so their contribution to every
     * day's bucket is a fixed count, added back in once. In practice this covers
     * ~45% of institutions (routinely-idle ones are common).
     *
     * For the rest, statuses are computed by sweepActiveStatuses() rather than by
     * calling resolve() once per (institution, day): profiling showed resolve()'s
     * per-call overhead (repeated Carbon object construction, Collection closures)
     * dominates over its algorithmic complexity, since institutions typically carry
     * few meetings — 16 tenants × 180 days took ~20s even after the idle-institution
     * skip. The sweep amortizes that by building each institution's sorted
     * meeting/check-in timeline once, then walking the requested dates in
     * chronological order with cursors instead of re-scanning from scratch.
     *
     * @param  list<int>  $tenantIds
     * @return list<array{date: string, all: int, needs_attention: int, overdue: int, approaching: int, no_activity: int, current: int}>
     */
    public function tenantStatusHistory(array $tenantIds, int $days): array
    {
        $institutions = DutyService::getTimelineInstitutionsForTenants($tenantIds, $this->authorizer);
        $institutions = $this->withoutExcludedInstitutionTypes($institutions);
        $institutions->each(fn (Institution $institution) => $institution->loadMissing(['meetings', 'checkIns', 'types']));

        [$idle, $active] = $institutions->partition(
            fn (Institution $institution) => $institution->meetings->isEmpty() && $institution->checkIns->isEmpty()
        );
        $idleCount = $idle->count();
        $totalCount = $institutions->count();

        $today = Carbon::today();
        $dates = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $dates[] = $today->copy()->subDays($offset)->endOfDay();
        }

        $statusesByDate = $this->sweepActiveStatuses($active, $dates);

        $series = [];
        foreach ($dates as $index => $date) {
            $tally = ['overdue' => 0, 'approaching' => 0, 'no_activity' => $idleCount, 'needs_attention' => $idleCount];
            foreach ($statusesByDate[$index] as $status) {
                match ($status) {
                    InstitutionActivityStatus::Overdue => $tally['overdue']++,
                    InstitutionActivityStatus::Approaching => $tally['approaching']++,
                    InstitutionActivityStatus::NoActivity => $tally['no_activity']++,
                    default => null,
                };
                if ($status->requiresAction()) {
                    $tally['needs_attention']++;
                }
            }

            $series[] = [
                'date' => $date->toDateString(),
                'all' => $totalCount,
                'needs_attention' => $tally['needs_attention'],
                'overdue' => $tally['overdue'],
                'approaching' => $tally['approaching'],
                'no_activity' => $tally['no_activity'],
                'current' => $totalCount - $tally['needs_attention'],
            ];
        }

        return $series;
    }

    /**
     * Computes each active institution's InstitutionActivityStatus for every date
     * in $dates (ascending, end-of-day instants), reusing precomputed sorted
     * meeting/check-in timelines and cursors instead of calling
     * InstitutionActivityStatusService::resolve() per (institution, day). Delegates
     * the actual status decision to InstitutionActivityStatusService::classify() —
     * the same rule resolve() uses — so the two can never diverge; only the (much
     * cheaper) bookkeeping of "what's the next/last meeting or check-in as of this
     * date" is reimplemented here.
     *
     * @param  Collection<int, Institution>  $institutions
     * @param  list<CarbonInterface>  $dates
     * @return list<list<InstitutionActivityStatus>> outer index matches $dates, inner index matches $institutions
     */
    private function sweepActiveStatuses(Collection $institutions, array $dates): array
    {
        $institutions = $institutions->values();

        // Precompute once per institution: sorted meeting instants (as both raw
        // timestamps, for cheap cursor comparisons, and reusable CarbonImmutable
        // objects, so effectiveDaysBetween() never reconstructs the same instant
        // twice), sorted check-in intervals, and the periodicity threshold.
        $meetingTimestamps = [];
        $meetingInstants = [];
        $checkInIntervals = [];
        $periodicityDays = [];
        $meetingCursor = [];

        foreach ($institutions as $i => $institution) {
            $sortedMeetings = $institution->meetings
                ->map(fn (Meeting $meeting) => CarbonImmutable::instance($meeting->start_time))
                ->sort(fn (CarbonImmutable $a, CarbonImmutable $b) => $a->getTimestamp() <=> $b->getTimestamp())
                ->values();

            $meetingInstants[$i] = $sortedMeetings->all();
            $meetingTimestamps[$i] = $sortedMeetings->map(fn (CarbonImmutable $instant) => $instant->getTimestamp())->all();
            $meetingCursor[$i] = 0;

            $checkInIntervals[$i] = $institution->checkIns
                ->map(fn (InstitutionCheckIn $checkIn) => [
                    'start' => CarbonImmutable::instance($checkIn->start_date)->startOfDay(),
                    'end' => CarbonImmutable::instance($checkIn->end_date)->startOfDay(),
                ])
                ->sortBy(fn (array $interval) => $interval['end']->getTimestamp())
                ->values()
                ->all();

            $periodicityDays[$i] = $institution->meeting_periodicity_days;
        }

        $statusesByDate = [];

        foreach ($dates as $dateIndex => $moment) {
            $momentTs = $moment->getTimestamp();
            $today = $moment->copy()->startOfDay();
            $todayTs = $today->getTimestamp();
            $statuses = [];

            foreach ($institutions as $i => $institution) {
                // Meetings with start_time <= moment have "happened"; advance the
                // cursor past them (dates are processed in ascending order, so this
                // pointer only ever moves forward across the whole sweep).
                while ($meetingCursor[$i] < count($meetingTimestamps[$i]) && $meetingTimestamps[$i][$meetingCursor[$i]] <= $momentTs) {
                    $meetingCursor[$i]++;
                }
                $hasUpcomingMeeting = $meetingCursor[$i] < count($meetingTimestamps[$i]);
                $lastMeetingInstant = $meetingCursor[$i] > 0 ? $meetingInstants[$i][$meetingCursor[$i] - 1] : null;

                $activeCheckIn = false;
                $latestCompletedCheckInEnd = null;
                foreach ($checkInIntervals[$i] as $interval) {
                    $startTs = $interval['start']->getTimestamp();
                    $endTs = $interval['end']->getTimestamp();

                    if ($startTs <= $todayTs && $endTs >= $todayTs) {
                        $activeCheckIn = true;
                    }
                    if ($endTs < $todayTs && ($latestCompletedCheckInEnd === null || $endTs > $latestCompletedCheckInEnd->getTimestamp())) {
                        $latestCompletedCheckInEnd = $interval['end'];
                    }
                }

                $checkInIsLatestActivity = $latestCompletedCheckInEnd !== null
                    && ($lastMeetingInstant === null || $latestCompletedCheckInEnd->endOfDay()->gt($lastMeetingInstant));
                $hasActivity = $checkInIsLatestActivity || $lastMeetingInstant !== null;

                $effectiveDays = 0;
                if ($hasActivity) {
                    $activityCalculationStart = $checkInIsLatestActivity
                        ? $latestCompletedCheckInEnd->addDay()
                        : $lastMeetingInstant;
                    $effectiveDays = $this->academicCalendar->effectiveDaysBetween($activityCalculationStart, $moment);
                }

                $statuses[] = $this->activityStatusService->classify(
                    hasUpcomingMeeting: $hasUpcomingMeeting,
                    hasActiveCheckIn: $activeCheckIn,
                    hasActivity: $hasActivity,
                    effectiveDays: $effectiveDays,
                    periodicityDays: $periodicityDays[$i],
                );
            }

            $statusesByDate[$dateIndex] = $statuses;
        }

        return $statusesByDate;
    }

    /**
     * Meetings of the given tenants' direct institutions inside a date window,
     * trimmed to exactly what the Gantt chart renders (server-side DTO mapping
     * keeps the payload small).
     *
     * @param  list<int>  $tenantIds
     * @return list<array<string, mixed>>
     */
    public function tenantMeetings(array $tenantIds, CarbonInterface $from, CarbonInterface $until): array
    {
        $institutionIds = $this->accessibleInstitutionIds($tenantIds);

        if ($institutionIds->isEmpty()) {
            return [];
        }

        $institutions = Institution::query()
            ->select('institutions.id', 'institutions.name')
            ->whereIn('institutions.id', $institutionIds)
            ->with([
                'meetings' => fn ($query) => $query
                    ->select('meetings.id', 'meetings.title', 'meetings.start_time', 'meetings.type')
                    ->whereBetween('meetings.start_time', [$from, $until])
                    ->with([
                        'agendaItems:id,meeting_id,title,type,brought_by_students',
                        'agendaItems.votes:id,agenda_item_id,decision,student_vote,student_benefit,is_main',
                        'fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                    ]),
            ])
            ->get();

        return $institutions
            ->flatMap(fn (Institution $institution) => $institution->meetings
                ->map(fn (Meeting $meeting) => $this->mapMeeting($meeting, $institution)))
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $tenantIds
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function accessibleInstitutionIds(array $tenantIds): \Illuminate\Support\Collection
    {
        $excludedTypeIds = $this->meetingSettings->getExcludedInstitutionTypeIds();

        return Institution::query()
            ->whereIn('tenant_id', $tenantIds)
            ->whereHas('tenant', fn (Builder $query) => $query->whereIn('type', TenantType::representationalValues()))
            ->when(
                $excludedTypeIds->isNotEmpty(),
                fn (Builder $query) => $query->whereDoesntHave(
                    'types',
                    fn (Builder $typeQuery) => $typeQuery->whereIn('types.id', $excludedTypeIds)
                )
            )
            ->pluck('institutions.id');
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array{
     *   users: array<int, array<string, mixed>>,
     *   pagination: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public function representativeUsers(
        array $tenantIds,
        string $category,
        ?string $search,
        int $perPage,
    ): array {
        $query = $this->representativeQuery($tenantIds);

        if (filled($search)) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('current_duties', function (Builder $dutyQuery) use ($search): void {
                        $dutyQuery->where('name', 'like', "%{$search}%")
                            ->orWhereHas('institution', fn (Builder $institutionQuery) => $institutionQuery
                                ->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        $thirtyDaysAgo = now()->subDays(30);

        if ($category === 'active') {
            $query->where('last_action', '>=', $thirtyDaysAgo);
        } elseif ($category === 'inactive') {
            $query->where(fn (Builder $query) => $query
                ->whereNull('last_action')
                ->orWhere('last_action', '<', $thirtyDaysAgo));
        }

        $query->orderByRaw('CASE WHEN last_action IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_action')
            ->orderBy('name');

        $paginator = $query
            ->select('id', 'name', 'email', 'profile_photo_path', 'last_action')
            ->with($this->representativeDutyRelations($tenantIds))
            ->paginate($perPage);

        return [
            'users' => $paginator->getCollection()
                ->map(fn (User $representative) => $this->mapRepresentative($representative))
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     */
    private function decorateInstitutions(Collection $institutions): void
    {
        $institutions->each(function (Institution $institution): void {
            $institution->setAttribute(
                'active_check_in',
                $institution->checkIns
                    ->where('end_date', '>=', now())
                    ->where('start_date', '<=', now())
                    ->first()
            );
            $institution->append(['has_public_meetings', 'meeting_periodicity_days']);
            $institution->setAttribute(
                'activity_status',
                $this->activityStatusService->resolve($institution)->toArray()
            );

            $institution->duties->each(function ($duty): void {
                $duty->users->each(function (User $representative): void {
                    $representative->makeVisible('last_action');
                    $representative->setAttribute(
                        'activity_category',
                        $this->activityCategory($representative->last_action)
                    );
                });
            });
        });
    }

    /**
     * Map an institution to the exact shape the tenant timeline UI consumes.
     * Plain arrays (not models) keep the payload small and prevent lazy-loaded
     * relation internals from leaking into the response.
     *
     * @return array<string, mixed>
     */
    private function mapInstitution(Institution $institution): array
    {
        $activeCheckIn = $institution->getAttribute('active_check_in');
        $lastMeetingDate = $institution->getAttribute('last_meeting_date');

        return [
            'id' => (string) $institution->id,
            'name' => $institution->name,
            'tenant_id' => $institution->tenant_id,
            'tenant' => $institution->tenant ? [
                'id' => $institution->tenant->id,
                'shortname' => $institution->tenant->shortname,
                'type' => $institution->tenant->type,
            ] : null,
            'meeting_periodicity_days' => $institution->meeting_periodicity_days,
            'has_public_meetings' => $institution->has_public_meetings,
            'upcoming_meetings_count' => (int) ($institution->upcoming_meetings_count ?? 0),
            'last_meeting_date' => is_string($lastMeetingDate)
                ? Carbon::parse($lastMeetingDate)->toISOString()
                : null,
            'activity_status' => $institution->getAttribute('activity_status'),
            'active_check_in' => $activeCheckIn ? [
                'id' => (string) $activeCheckIn->id,
                'institution_id' => (string) $activeCheckIn->institution_id,
                'start_date' => $activeCheckIn->start_date->toDateString(),
                'end_date' => $activeCheckIn->end_date->toDateString(),
                'note' => $activeCheckIn->note,
            ] : null,
            'check_ins' => $institution->checkIns
                ->map(fn ($checkIn) => [
                    'id' => (string) $checkIn->id,
                    'institution_id' => (string) $checkIn->institution_id,
                    'start_date' => $checkIn->start_date->toDateString(),
                    'end_date' => $checkIn->end_date->toDateString(),
                    'note' => $checkIn->note,
                ])
                ->values()
                ->all(),
            'duties' => $institution->duties
                ->map(fn ($duty) => [
                    'id' => (string) $duty->id,
                    'users' => $duty->users
                        ->map(fn (User $representative) => [
                            'id' => (string) $representative->id,
                            'name' => $representative->name,
                            'profile_photo_path' => $representative->profile_photo_path,
                            'last_action' => $representative->last_action?->toISOString(),
                            'activity_category' => $representative->getAttribute('activity_category'),
                            'pivot' => [
                                'start_date' => $representative->pivot?->start_date,
                                'end_date' => $representative->pivot?->end_date,
                            ],
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMeeting(Meeting $meeting, Institution $institution): array
    {
        $agendaItems = $meeting->agendaItems;

        return [
            'id' => (string) $meeting->id,
            'institution_id' => (string) $institution->id,
            'institution' => (string) ($institution->name ?? ''),
            'start_time' => $meeting->start_time->toISOString(),
            'title' => $meeting->title,
            'type_slug' => $meeting->type?->value,
            'completion_status' => $meeting->completion_status,
            'has_report' => $meeting->has_report,
            'has_protocol' => $meeting->has_protocol,
            'agenda_items' => $agendaItems
                ->take(4)
                ->map(fn (AgendaItem $item) => $this->mapAgendaItem($item))
                ->values()
                ->all(),
            'agenda_items_count' => $agendaItems->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAgendaItem(AgendaItem $item): array
    {
        $mainVote = $item->votes->firstWhere('is_main', true) ?? $item->votes->first();

        return [
            'id' => (string) $item->id,
            'title' => (string) ($item->title ?? ''),
            'type' => $item->type?->value,
            'student_vote' => $mainVote?->student_vote,
            'decision' => $mainVote?->decision,
        ];
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @return array<string, int>
     */
    private function institutionSummary(Collection $institutions): array
    {
        $statuses = $institutions->pluck('activity_status.status')->countBy();
        $needsAttention = $institutions->filter(
            fn (Institution $institution) => (bool) data_get($institution, 'activity_status.requires_action')
        )->count();

        return [
            'all' => $institutions->count(),
            'needs_attention' => $needsAttention,
            'overdue' => (int) $statuses->get('overdue', 0),
            'approaching' => (int) $statuses->get('approaching', 0),
            'no_activity' => (int) $statuses->get('no_activity', 0),
            'current' => $institutions->count() - $needsAttention,
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array{
     *   stats: array<string, int>,
     *   preview_users: array<int, array<string, mixed>>
     * }
     */
    private function representativeActivitySummary(array $tenantIds): array
    {
        $query = $this->representativeQuery($tenantIds);
        $today = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7);
        $thirtyDaysAgo = now()->subDays(30);

        $aggregate = (clone $query)->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN last_action >= ? THEN 1 ELSE 0 END) as active_today', [$today])
            ->selectRaw('SUM(CASE WHEN last_action >= ? THEN 1 ELSE 0 END) as active_last_7_days', [$sevenDaysAgo])
            ->selectRaw('SUM(CASE WHEN last_action >= ? THEN 1 ELSE 0 END) as active_last_30_days', [$thirtyDaysAgo])
            ->selectRaw('SUM(CASE WHEN last_action IS NULL THEN 1 ELSE 0 END) as never_logged_in')
            ->first();

        $relations = $this->representativeDutyRelations($tenantIds);

        $inactiveUsers = (clone $query)
            ->select('id', 'name', 'email', 'profile_photo_path', 'last_action')
            ->where(fn (Builder $query) => $query
                ->whereNull('last_action')
                ->orWhere('last_action', '<', $thirtyDaysAgo))
            ->orderByRaw('CASE WHEN last_action IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_action')
            ->limit(self::REPRESENTATIVE_PREVIEW_LIMIT)
            ->with($relations)
            ->get();

        $activeUsers = (clone $query)
            ->select('id', 'name', 'email', 'profile_photo_path', 'last_action')
            ->where('last_action', '>=', $thirtyDaysAgo)
            ->orderByDesc('last_action')
            ->limit(self::REPRESENTATIVE_PREVIEW_LIMIT)
            ->with($relations)
            ->get();

        return [
            'stats' => [
                'total' => (int) data_get($aggregate, 'total', 0),
                'activeToday' => (int) data_get($aggregate, 'active_today', 0),
                'activeLast7Days' => (int) data_get($aggregate, 'active_last_7_days', 0),
                'activeLast30Days' => (int) data_get($aggregate, 'active_last_30_days', 0),
                'neverLoggedIn' => (int) data_get($aggregate, 'never_logged_in', 0),
            ],
            'preview_users' => $inactiveUsers
                ->merge($activeUsers)
                ->unique('id')
                ->map(fn (User $representative) => $this->mapRepresentative($representative))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @return Builder<User>
     */
    private function representativeQuery(array $tenantIds): Builder
    {
        $excludedTypeIds = $this->meetingSettings->getExcludedInstitutionTypeIds();

        return User::query()
            ->whereHas('current_duties', function (Builder $query) use ($tenantIds, $excludedTypeIds): void {
                $query->whereHas('institution', function (Builder $institutionQuery) use ($tenantIds, $excludedTypeIds): void {
                    $institutionQuery->whereIn('tenant_id', $tenantIds);

                    if ($excludedTypeIds->isNotEmpty()) {
                        $institutionQuery->whereDoesntHave(
                            'types',
                            fn (Builder $typeQuery) => $typeQuery->whereIn('types.id', $excludedTypeIds)
                        );
                    }
                });
            });
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array<string, \Closure>
     */
    private function representativeDutyRelations(array $tenantIds): array
    {
        $excludedTypeIds = $this->meetingSettings->getExcludedInstitutionTypeIds();

        return [
            'current_duties' => function ($query) use ($tenantIds, $excludedTypeIds): void {
                $query->select('duties.id', 'duties.name', 'duties.institution_id')
                    ->whereHas('institution', function (Builder $institutionQuery) use ($tenantIds, $excludedTypeIds): void {
                        $institutionQuery->whereIn('tenant_id', $tenantIds);

                        if ($excludedTypeIds->isNotEmpty()) {
                            $institutionQuery->whereDoesntHave(
                                'types',
                                fn (Builder $typeQuery) => $typeQuery->whereIn('types.id', $excludedTypeIds)
                            );
                        }
                    })
                    ->with('institution:id,name,tenant_id');
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRepresentative(User $representative): array
    {
        $representative->makeVisible('last_action');

        return [
            'id' => (string) $representative->id,
            'name' => $representative->name,
            'email' => $representative->email,
            'profile_photo_path' => $representative->profile_photo_path,
            'last_action' => $representative->last_action?->toISOString(),
            'category' => $this->activityCategory($representative->last_action),
            'duties' => $representative->current_duties
                ->map(fn ($duty) => [
                    'id' => (string) $duty->id,
                    'name' => data_get($duty, 'name'),
                    'institution_name' => data_get($duty, 'institution.name'),
                ])
                ->values()
                ->all(),
        ];
    }

    private function activityCategory(?CarbonInterface $lastAction): string
    {
        if ($lastAction === null) {
            return 'never';
        }

        if ($lastAction->greaterThanOrEqualTo(now()->startOfDay())) {
            return 'today';
        }

        if ($lastAction->greaterThanOrEqualTo(now()->subDays(7))) {
            return 'week';
        }

        if ($lastAction->greaterThanOrEqualTo(now()->subDays(30))) {
            return 'month';
        }

        return 'stale';
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, Institution>
     */
    private function withoutExcludedInstitutionTypes(Collection $institutions): Collection
    {
        $excludedTypeIds = $this->meetingSettings->getExcludedInstitutionTypeIds();

        if ($excludedTypeIds->isEmpty()) {
            return $institutions;
        }

        return $institutions
            ->filter(fn (Institution $institution) => $institution->types
                ->pluck('id')
                ->intersect($excludedTypeIds)
                ->isEmpty())
            ->values();
    }
}
