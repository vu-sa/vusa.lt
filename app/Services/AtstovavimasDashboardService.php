<?php

namespace App\Services;

use App\Enums\InstitutionActivityStatus;
use App\Enums\ResponsibilityPurpose;
use App\Enums\TenantType;
use App\Jobs\ReconstructInstitutionStatusHistory;
use App\Models\Institution;
use App\Models\InstitutionStatusSnapshot;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\User;
use App\Models\Vote;
use App\Services\Institutions\AtstovavimasInstitutionPresenter;
use App\Services\Institutions\InstitutionQueries;
use App\Settings\AtstovavimasSettings;
use App\Settings\MeetingSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;

class AtstovavimasDashboardService
{
    public const string PROJECTION_FULL = 'full';

    public const string PROJECTION_PUBLIC = 'public';

    public const string PROJECTION_RESTRICTED = 'restricted';

    private const array PROJECTION_RANK = [self::PROJECTION_RESTRICTED => 0, self::PROJECTION_PUBLIC => 1, self::PROJECTION_FULL => 2];

    public function __construct(
        private readonly InstitutionActivityStatusService $activityStatusService,
        private readonly InstitutionStatusReplayService $replay,
        private readonly InstitutionStatusSnapshotService $snapshots,
        private readonly ModelAuthorizer $authorizer,
        private readonly MeetingSettings $meetingSettings,
        private readonly AtstovavimasSettings $atstovavimasSettings,
        private readonly InstitutionScopeResolver $scopeResolver,
    ) {}

    /**
     * Tenant timeline for the ViSAK dashboard: direct institutions of the given
     * tenants only (relationships are intentionally not resolved — the tenant
     * Gantt shows each tenant's own institutions), with server-computed activity
     * statuses and summaries. Meetings are served separately via ganttMeetings().
     *
     * @param  list<int>  $tenantIds
     * @return array{
     *   institutions: list<array<string, mixed>>,
     *   institution_summary: array<string, int>,
     *   representative_activity: array<string, mixed>
     * }
     */
    public function tenantTimeline(User $actor, array $tenantIds): array
    {
        $institutions = InstitutionQueries::getTimelineInstitutionsForTenants($actor, $tenantIds, $this->authorizer);
        $rows = $institutions->map(fn (Institution $institution): array => [
            ...$this->mapInstitution($institution),
            'in_summary' => RepStatisticsScope::counts($institution),
        ])->values();

        return [
            'institutions' => $rows->all(),
            'institution_summary' => $this->institutionSummary($rows->where('in_summary', true)),
            'representative_activity' => $this->representativeActivitySummary($tenantIds),
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array{points: list<array>, missingDates: list<string>, pending: bool, baselineDate: string|null}
     */
    public function tenantStatusHistory(array $tenantIds, int $days, bool $refresh = false): array
    {
        $today = CarbonImmutable::today();
        $dates = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $dates[] = $today->subDays($offset);
        }

        // Closed days never change, so the cache holds the finished points, not the snapshot models.
        $closed = RepStatisticsCache::remember('closed-statuses:'.implode(',', $tenantIds).':'.$days, function () use ($tenantIds, $dates, $today): array {
            $stored = InstitutionStatusSnapshot::query()
                ->whereIn('tenant_id', $tenantIds)
                ->whereBetween('taken_on', [$dates[0]->toDateString(), $today->subDay()->toDateString()])
                ->get()
                ->groupBy(fn (InstitutionStatusSnapshot $snapshot): string => $snapshot->taken_on->toDateString());

            $points = [];
            $missing = [];
            foreach ($dates as $date) {
                $key = $date->toDateString();
                $snapshots = $stored->get($key, collect());
                if ($date->isToday()) {
                    continue;
                }
                if ($snapshots->count() !== count($tenantIds)) {
                    $missing[] = $key;

                    continue;
                }
                $counts = collect(InstitutionStatusSnapshot::SUMMED_COLUMNS)->mapWithKeys(fn (string $column): array => [$column => (int) $snapshots->sum($column)])->all();
                foreach (['held', 'registered'] as $kind) {
                    if ($snapshots->every(fn ($row) => $row->meeting_ids !== null)) {
                        $counts['meetings_'.$kind] = $snapshots->flatMap(fn ($row) => $row->meeting_ids[$kind] ?? [])->unique()->count();
                    }
                }
                $points[$key] = ['date' => $key, ...$counts, 'reconstructed' => $snapshots->contains('is_reconstructed', true)];
            }

            $previous = $stored->get($today->subDay()->toDateString(), collect());
            $previousStatuses = $previous->count() === count($tenantIds) && $previous->every(fn ($row) => $row->institution_statuses !== null)
                ? $previous->flatMap(fn ($row) => $row->institution_statuses)->all()
                : null;

            return ['points' => $points, 'missing' => $missing, 'previousStatuses' => $previousStatuses];
        }, history: true, refresh: $refresh, tenantIds: $tenantIds);

        if ($closed['missing'] !== []) {
            ReconstructInstitutionStatusHistory::dispatch($closed['missing'][0], end($closed['missing']))->afterCommit();
        }

        $live = RepStatisticsCache::remember('status-today:'.implode(',', $tenantIds), function () use ($tenantIds, $today, $closed): array {
            $result = $this->replayDays($tenantIds, [$today]);
            if ($closed['previousStatuses'] !== null) {
                $after = $result[$today->toDateString()]['institution_statuses'];
                $result[$today->toDateString()] = [
                    ...$result[$today->toDateString()],
                    ...$this->snapshots->transitions(array_map(InstitutionActivityStatus::from(...), $closed['previousStatuses']), array_map(InstitutionActivityStatus::from(...), $after)),
                ];
            }

            return $result;
        }, refresh: $refresh, tenantIds: $tenantIds);

        $points = [];
        foreach ($dates as $date) {
            $key = $date->toDateString();

            if ($date->isToday()) {
                $points[] = ['date' => $key, ...Arr::except($live[$key], ['institution_statuses', 'meeting_ids']), 'reconstructed' => false];
            } elseif (isset($closed['points'][$key])) {
                $points[] = $closed['points'][$key];
            }
        }

        return [
            'points' => $points,
            'missingDates' => $closed['missing'],
            'pending' => $closed['missing'] !== [],
            'baselineDate' => app(RepMetricReportService::class)->baselineCadence()?->end_date->toDateString(),
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @param  list<CarbonImmutable>  $dates  ascending, not necessarily consecutive
     * @return array<string, array<string, int|array>>
     */
    private function replayDays(array $tenantIds, array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        $institutions = $this->replay->institutionsForTenants($tenantIds);
        $summaries = [];

        // Entering and leaving compare a day with the one before it, so each run of days replays together.
        $run = [];
        foreach ([...$dates, null] as $date) {
            if ($run !== [] && ($date === null || ! $date->subDay()->isSameDay(end($run)))) {
                $summaries += $this->snapshots->summarizeDays($institutions, $run);
                $run = [];
            }

            if ($date !== null) {
                $run[] = $date;
            }
        }

        return $summaries;
    }

    /**
     * Which institutions the padaliniai Gantt draws for the user, and how much of each: every body
     * of a padalinys they manage, their own and authorized related bodies in full; bodies whose
     * meetings are public as `public`; other related bodies as `restricted` (no agendas).
     *
     * @param  list<int>  $tenantIds
     * @return SupportCollection<string, string> institution id => projection
     */
    public function ganttInstitutionAccess(User $user, array $tenantIds): SupportCollection
    {
        $inSelection = fn () => Institution::query()->active()
            ->whereIn('tenant_id', $tenantIds)
            ->whereHas('tenant', fn (Builder $query) => $query->whereIn('type', TenantType::representationalValues()));

        $access = [];
        $grant = function (iterable $institutionIds, string $projection) use (&$access): void {
            foreach ($institutionIds as $institutionId) {
                $current = $access[(string) $institutionId] ?? null;

                if ($current === null || self::PROJECTION_RANK[$current] < self::PROJECTION_RANK[$projection]) {
                    $access[(string) $institutionId] = $projection;
                }
            }
        };

        $publicTypeIds = $this->meetingSettings->getPublicMeetingInstitutionTypeIds();
        if ($publicTypeIds->isNotEmpty()) {
            $grant($inSelection()
                ->whereHas('types', fn (Builder $query) => $query->whereIn('institution_types.id', $publicTypeIds))
                ->pluck('institutions.id'), self::PROJECTION_PUBLIC);
        }

        $managedTenantIds = $this->atstovavimasSettings->getVisibleTenantIds($user)->map(fn ($id) => (int) $id);
        if ($managedTenantIds->intersect($tenantIds)->isNotEmpty()) {
            $grant($inSelection()->whereIn('tenant_id', $managedTenantIds)->pluck('institutions.id'), self::PROJECTION_FULL);
        }

        $institutionAccess = app(InstitutionAccessService::class);
        $dutyIds = $institutionAccess->getUserDutyInstitutionIds($user);
        $ownIds = Institution::query()->active()
            ->whereKey($dutyIds->merge($institutionAccess->getSecretariedInstitutionIds($user))->unique()->values())
            ->pluck('id');

        $candidates = $ownIds->mapWithKeys(fn (string $id): array => [$id => self::PROJECTION_FULL])->all();
        $relations = app(InstitutionRelationService::class);
        foreach ($ownIds->intersect($dutyIds) as $institutionId) {
            foreach ($relations->relatedTo($institutionId) as $related) {
                $relatedId = $related['institution_id'];

                if (($candidates[$relatedId] ?? null) !== self::PROJECTION_FULL) {
                    $candidates[$relatedId] = $related['authorized'] ? self::PROJECTION_FULL : self::PROJECTION_RESTRICTED;
                }
            }
        }

        if ($candidates !== []) {
            foreach ($inSelection()->whereIn('institutions.id', array_keys($candidates))->pluck('institutions.id') as $institutionId) {
                $grant([$institutionId], $candidates[(string) $institutionId]);
            }
        }

        return collect($access);
    }

    /** @return SupportCollection<string, string> */
    public function representativeInstitutionAccess(User $user): SupportCollection
    {
        $own = InstitutionQueries::representativeDashboardInstitutions($user, withSummary: false);
        $related = InstitutionQueries::relatedRepresentativeDashboardInstitutions($own, withSummary: false);

        return collect($own->modelKeys())
            ->mapWithKeys(fn (string $id): array => [$id => self::PROJECTION_FULL])
            ->union($related->mapWithKeys(fn (Institution $institution): array => [
                $institution->id => $institution->getAttribute('authorized') ? self::PROJECTION_FULL : self::PROJECTION_RESTRICTED,
            ]));
    }

    /**
     * @param  list<int>  $tenantIds
     * @return SupportCollection<string, string>
     */
    public function cachedGanttInstitutionAccess(User $actor, array $tenantIds, bool $refresh = false): SupportCollection
    {
        $tenantIds = array_unique($tenantIds);
        sort($tenantIds, SORT_NUMERIC);

        return RepStatisticsCache::remember(
            'gantt-access:v2:'.$actor->id.':'.InstitutionRelationService::version().':'.implode(',', $tenantIds),
            fn () => $this->ganttInstitutionAccess($actor, $tenantIds),
            refresh: $refresh,
            tenantIds: $tenantIds,
            ttl: 120,
        );
    }

    /**
     * Rows outside full access keep the meeting anchors and check-ins with a null activity status.
     *
     * @param  SupportCollection<string, string>  $access  from ganttInstitutionAccess()
     * @return list<array<string, mixed>>
     */
    public function ganttInstitutions(SupportCollection $access): array
    {
        $institutions = InstitutionQueries::getTimelineInstitutions($access->keys());

        return $institutions
            ->map(fn (Institution $institution): array => $this->mapInstitution(
                $institution,
                authorized: $access[$institution->id] === self::PROJECTION_FULL,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  SupportCollection<string, string>  $access
     * @return array{users: object, institutions: object}
     */
    public function ganttMembers(SupportCollection $access): array
    {
        $users = [];
        $institutions = [];
        foreach (InstitutionQueries::getGanttMemberInstitutions($access->keys()) as $institution) {
            $fullAccess = $access[$institution->id] === self::PROJECTION_FULL;
            $periods = [];
            $activity = [];
            foreach (AtstovavimasInstitutionPresenter::membershipTerms($institution) as ['term' => $term, 'user' => $user]) {
                $users[$user->id] ??= ['name' => $user->name, 'profile_photo_path' => $user->profile_photo_path];
                $periods[] = [
                    'user_id' => $user->id,
                    'duty_id' => $term->duty_id,
                    'start_date' => $term->start_date->toDateString(),
                    'end_date' => $term->end_date?->toDateString(),
                ];
                if ($fullAccess) {
                    $activity[$user->id] ??= $this->activityCategory($user->last_action);
                }
            }

            $institutions[$institution->id] = ['periods' => $periods];
            if ($fullAccess) {
                $institutions[$institution->id]['activity'] = (object) $activity;
            }
        }

        return ['users' => (object) $users, 'institutions' => (object) $institutions];
    }

    /**
     * Meetings of the Gantt's institutions inside a date window, trimmed to exactly what the chart
     * renders. Only full rows calculate recording state, and restricted rows report zero items.
     *
     * @param  SupportCollection<string, string>  $access  institution id => projection
     * @return list<array<string, mixed>>
     */
    public function ganttMeetings(
        User $actor,
        SupportCollection $access,
        CarbonInterface $from,
        CarbonInterface $until,
        bool $includeRestrictedFiles = false,
    ): array {
        if ($access->isEmpty()) {
            return [];
        }

        $institutions = Institution::query()->active()
            ->select(['institutions.id', 'institutions.name'])
            ->whereIn('institutions.id', $access->keys())
            ->with([
                'meetings' => fn ($query) => $query
                    ->select('meetings.id', 'meetings.title', 'meetings.start_time', 'meetings.type')
                    ->whereBetween('meetings.start_time', [$from, $until])
                    ->withCount('agendaItems')
                    ->with([
                        'fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                        'calendarEvent:id,meeting_id,is_draft',
                    ]),
            ])
            ->get();

        $fullMeetings = new Collection($institutions
            ->filter(fn (Institution $institution): bool => $access[$institution->id] === self::PROJECTION_FULL)
            ->flatMap(fn (Institution $institution) => $institution->meetings)->all());
        $fullMeetings->load([
            'agendaItems:id,meeting_id,type,is_private',
            'agendaItems.votes:id,agenda_item_id,decision,student_vote,student_benefit,is_main,is_consensus',
            'institutions.types',
        ]);

        if (! $actor->isSuperAdmin()) {
            $privateMeetings = $fullMeetings->filter(fn (Meeting $meeting): bool => $meeting->agendaItems->contains('is_private', true));
            $privateMeetings->load('tenants');
            Meeting::preloadMembershipAtMeetingTime($actor, $privateMeetings);
            foreach ($privateMeetings as $meeting) {
                $this->setPrivateAgendaTenants($meeting, $meeting->agendaItems);
            }
        }

        return $institutions
            ->flatMap(fn (Institution $institution) => $institution->meetings
                ->map(fn (Meeting $meeting) => $this->mapMeeting($actor, $meeting, $institution, $access[(string) $institution->id], $includeRestrictedFiles)))
            ->values()
            ->all();
    }

    /** @return array{agenda_items: list<array{id: string, title: string, type: string|null, votes: list<array{student_vote: string|null, decision: string|null, is_consensus: bool}>}|array{id: string, title: string, order: int, is_private: true, is_redacted: true}>, agenda_items_count: int} */
    public function ganttMeetingAgenda(User $actor, string $institutionId, string $meetingId, string $projection): array
    {
        $institution = Institution::query()->active()->findOrFail($institutionId, ['id']);
        $meeting = $institution->meetings()
            ->select('meetings.id', 'meetings.start_time')
            ->whereKey($meetingId)
            ->when($projection !== self::PROJECTION_RESTRICTED, fn ($query) => $query->withCount('agendaItems'))
            ->firstOrFail();

        if ($projection === self::PROJECTION_RESTRICTED) {
            return ['agenda_items' => [], 'agenda_items_count' => 0];
        }

        $items = $meeting->agendaItems()
            ->with('votes:id,agenda_item_id,decision,student_vote,is_main,is_consensus')
            ->orderBy('order')
            ->limit(4)
            ->get(['id', 'meeting_id', 'title', 'type', 'order', 'is_private', 'public_title']);

        if ($items->contains('is_private', true) && ! $actor->isSuperAdmin()) {
            $meeting->load(['institutions:id', 'tenants']);
            $this->setPrivateAgendaTenants($meeting, $items);
        }

        return [
            'agenda_items' => $items->map(fn (AgendaItem $item): array => $this->mapAgendaItem($actor, $item))->all(),
            'agenda_items_count' => (int) $meeting->getAttribute('agenda_items_count'),
        ];
    }

    /** @param  Collection<int, AgendaItem>  $items */
    private function setPrivateAgendaTenants(Meeting $meeting, Collection $items): void
    {
        foreach ($items as $item) {
            if ($item->is_private) {
                $item->setRelation('meeting', $meeting);
                $item->setRelation('tenants', $meeting->tenants);
            }
        }
    }

    /**
     * Map an institution to the exact shape the tenant timeline UI consumes.
     * Plain arrays (not models) keep the payload small and prevent lazy-loaded
     * relation internals from leaking into the response.
     *
     * @return array<string, mixed>
     */
    private function mapInstitution(Institution $institution, bool $authorized = true): array
    {
        $now = now();
        $activeCheckIn = $institution->checkIns->where('end_date', '>=', $now)->where('start_date', '<=', $now)->first();

        return [
            'id' => (string) $institution->id,
            'name' => $institution->name,
            'is_active' => (bool) $institution->is_active,
            'tenant_id' => $institution->tenant_id,
            'tenant' => $institution->tenant ? [
                'id' => $institution->tenant->id,
                'shortname' => $institution->tenant->shortname,
                'type' => $institution->tenant->type,
            ] : null,
            'types' => $institution->types
                ->map(fn ($type) => ['id' => (string) $type->id, 'title' => $type->title])
                ->values()
                ->all(),
            'meeting_periodicity_days' => $institution->meeting_periodicity_days,
            // One of VU SA's own bodies rather than one it delegates into. Inherited down the
            // type tree, so it covers children the settings' flat exclusion list misses.
            'is_internal' => $this->scopeResolver->forInstitution($institution)->isInternal(),
            'has_public_meetings' => $institution->has_public_meetings,
            'upcoming_meetings_count' => (int) ($institution->upcoming_meetings_count ?? 0),
            'last_meeting_date' => $institution->getAttribute('last_meeting_date'),
            'next_meeting_date' => $institution->getAttribute('next_meeting_date'),
            'last_activity_at' => $institution->getAttribute('last_activity_at'),
            'activity_status' => $authorized ? $this->activityStatusService->resolve($institution)->toArray() : null,
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
            ...($authorized ? [] : ['authorized' => false]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMeeting(User $actor, Meeting $meeting, Institution $institution, string $projection, bool $includeRestrictedFiles): array
    {
        $isFull = $projection === self::PROJECTION_FULL;
        $isRestricted = $projection === self::PROJECTION_RESTRICTED;
        $showFiles = $isFull || ($isRestricted && $includeRestrictedFiles);
        $calendarEvent = $meeting->calendarEvent;
        $outcome = $isFull ? $meeting->outcomeSummary($actor) : null;

        return [
            'id' => (string) $meeting->id,
            'institution_id' => (string) $institution->id,
            'institution' => (string) ($institution->name ?? ''),
            'start_time' => $meeting->start_time->toISOString(),
            'title' => $meeting->title,
            'type_slug' => $meeting->type?->value,
            'completion_status' => $outcome['completion_status'] ?? null,
            'vote_alignment_status' => $outcome['vote_alignment_status'] ?? null,
            'requires_student_perspective' => $isFull ? $meeting->requiresStudentPerspective() : null,
            'has_report' => $showFiles && $meeting->has_report,
            'has_protocol' => $showFiles && $meeting->has_protocol,
            // Announced in the public calendar, and whether that announcement is still a
            // draft — a drafted event is invisible to everyone but the admins.
            'has_calendar_event' => $isFull && $calendarEvent !== null,
            'calendar_event_is_draft' => $isFull && $calendarEvent !== null && $calendarEvent->is_draft,
            ...($isRestricted ? ['authorized' => false] : []),
            'agenda_items_count' => $isRestricted ? 0 : (int) $meeting->getAttribute('agenda_items_count'),
        ];
    }

    /**
     * @return array{id: string, title: string, type: string|null, votes: list<array{student_vote: string|null, decision: string|null, is_consensus: bool}>}|array{id: string, title: string, order: int, is_private: true, is_redacted: true}
     */
    private function mapAgendaItem(User $actor, AgendaItem $item): array
    {
        if ($item->is_private && ! AgendaItemPresenter::canRead($item, $actor)) {
            return AgendaItemPresenter::redacted($item);
        }

        return [
            'id' => (string) $item->id,
            'title' => (string) ($item->title ?? ''),
            'type' => $item->type?->value,
            'votes' => $item->outcomeVotes()
                ->map(fn (Vote $vote): array => [
                    'student_vote' => $vote->student_vote,
                    'decision' => $vote->decision,
                    'is_consensus' => $vote->is_consensus,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  SupportCollection<int, array<string, mixed>>  $institutions
     * @return array<string, int>
     */
    private function institutionSummary(SupportCollection $institutions): array
    {
        $statuses = $institutions->pluck('activity_status.status')->countBy();
        $needsAttention = $institutions->where('activity_status.requires_action', true)->count();

        return [
            'all' => $institutions->count(),
            'needs_attention' => $needsAttention,
            'overdue' => (int) $statuses->get('overdue', 0),
            'approaching' => (int) $statuses->get('approaching', 0),
            'no_activity' => (int) $statuses->get('no_activity', 0),
            'recent_check_in' => (int) $statuses->get('recent_check_in', 0),
            'current' => $institutions->count() - $needsAttention - (int) $statuses->get('recent_check_in', 0),
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array{stats: array<string, int>}
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

        return [
            'stats' => [
                'total' => (int) data_get($aggregate, 'total', 0),
                'activeToday' => (int) data_get($aggregate, 'active_today', 0),
                'activeLast7Days' => (int) data_get($aggregate, 'active_last_7_days', 0),
                'activeLast30Days' => (int) data_get($aggregate, 'active_last_30_days', 0),
                'neverLoggedIn' => (int) data_get($aggregate, 'never_logged_in', 0),
            ],
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @return Builder<User>
     */
    private function representativeQuery(array $tenantIds): Builder
    {
        return User::query()
            ->whereHas('current_duties', fn (Builder $query) => $query
                ->where('duties.is_active', true)
                ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->whereIn('institution_id', RepStatisticsScope::institutions($tenantIds)->select('institutions.id')));
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
}
