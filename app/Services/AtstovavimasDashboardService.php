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
use App\Services\ResourceServices\DutyService;
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
    private const int REPRESENTATIVE_PREVIEW_LIMIT = 3;

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
    public function tenantTimeline(array $tenantIds): array
    {
        $institutions = DutyService::getTimelineInstitutionsForTenants($tenantIds, $this->authorizer);
        $this->decorateInstitutions($institutions);
        $counted = $this->countedInstitutions($institutions);
        $countedIds = $counted->pluck('id')->map(fn ($id) => (string) $id)->flip();

        return [
            // Every row is drawn; the chart's own toggle decides which are shown. The tiles and the
            // attention list stay on the set the settings call formal (`in_summary`), so the two agree.
            'institutions' => $institutions
                ->map(fn (Institution $institution) => [
                    ...$this->mapInstitution($institution),
                    'in_summary' => $countedIds->has((string) $institution->id),
                ])
                ->values()
                ->all(),
            'institution_summary' => $this->institutionSummary($counted),
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

        $ownInstitutions = Institution::query()->active()
            ->whereIn('id', $user->authorization_duties()->pluck('institution_id')
                ->merge($user->secretariedInstitutions()->pluck('institutions.id'))
                ->filter()
                ->unique())
            ->get();

        $candidates = $ownInstitutions->mapWithKeys(fn (Institution $institution) => [(string) $institution->id => self::PROJECTION_FULL])->all();
        foreach ($ownInstitutions as $institution) {
            foreach (app(InstitutionRelationService::class)->relatedTo($institution->id) as $related) {
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

    /**
     * The padaliniai Gantt rows. Rows outside full access keep meetings' anchors, members and
     * check-ins, but not the health status or members' activity.
     *
     * @param  SupportCollection<string, string>  $access  from ganttInstitutionAccess()
     * @return list<array<string, mixed>>
     */
    public function ganttInstitutions(SupportCollection $access): array
    {
        $institutions = DutyService::getTimelineInstitutions($access->keys());
        $this->decorateInstitutions($institutions);

        return $institutions
            ->map(function (Institution $institution) use ($access): array {
                $row = $this->mapInstitution($institution);

                if ($access[(string) $institution->id] === self::PROJECTION_FULL) {
                    return $row;
                }

                return [
                    ...$row,
                    'authorized' => false,
                    'activity_status' => null,
                    'duties' => array_map(fn (array $duty) => [
                        ...$duty,
                        'users' => array_map(
                            fn (array $representative) => Arr::except($representative, ['last_action', 'activity_category']),
                            $duty['users'],
                        ),
                    ], $row['duties']),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Meetings of the Gantt's institutions inside a date window, trimmed to exactly what the chart
     * renders. Outside full access the recording state is left out; `restricted` rows also lose
     * their agendas.
     *
     * @param  SupportCollection<string, string>  $access  from ganttInstitutionAccess()
     * @return list<array<string, mixed>>
     */
    public function ganttMeetings(SupportCollection $access, CarbonInterface $from, CarbonInterface $until): array
    {
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
                    ->with([
                        'agendaItems:id,meeting_id,title,type,brought_by_students,order,is_private,public_title',
                        'agendaItems.votes:id,agenda_item_id,decision,student_vote,student_benefit,is_main,is_consensus',
                        'fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                        // Eager: mapMeeting asks every meeting in the window whether it is announced.
                        'calendarEvent:id,meeting_id,is_draft',
                        // completion_status asks requiresStudentPerspective(), which otherwise loads these per meeting.
                        'institutions.types',
                    ]),
            ])
            ->get();

        return $institutions
            ->flatMap(fn (Institution $institution) => $institution->meetings
                ->map(fn (Meeting $meeting) => $this->mapMeeting($meeting, $institution, $access[(string) $institution->id])))
            ->values()
            ->all();
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
                        $dutyQuery->whereTranslationLike('name', $search)
                            ->orWhereHas('institution', fn (Builder $institutionQuery) => $institutionQuery
                                ->whereTranslationLike('name', $search));
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
            // ISO strings from DutyService::withActivityDates(); the Gantt sorts and sections by them.
            'last_meeting_date' => $institution->getAttribute('last_meeting_date'),
            'next_meeting_date' => $institution->getAttribute('next_meeting_date'),
            'last_activity_at' => $institution->getAttribute('last_activity_at'),
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
    private function mapMeeting(Meeting $meeting, Institution $institution, string $projection = self::PROJECTION_FULL): array
    {
        $isFull = $projection === self::PROJECTION_FULL;
        $agendaItems = $projection === self::PROJECTION_RESTRICTED ? new Collection : $meeting->agendaItems;
        $calendarEvent = $meeting->calendarEvent;

        return [
            'id' => (string) $meeting->id,
            'institution_id' => (string) $institution->id,
            'institution' => (string) ($institution->name ?? ''),
            'start_time' => $meeting->start_time->toISOString(),
            'title' => $meeting->title,
            'type_slug' => $meeting->type?->value,
            'completion_status' => $isFull ? $meeting->completion_status : null,
            'has_report' => $isFull && $meeting->has_report,
            'has_protocol' => $isFull && $meeting->has_protocol,
            // Announced in the public calendar, and whether that announcement is still a
            // draft — a drafted event is invisible to everyone but the admins.
            'has_calendar_event' => $isFull && $calendarEvent !== null,
            'calendar_event_is_draft' => $isFull && $calendarEvent !== null && $calendarEvent->is_draft,
            ...($projection === self::PROJECTION_RESTRICTED ? ['authorized' => false] : []),
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
        if ($item->is_private && ! AgendaItemPresenter::canRead($item, auth()->user())) {
            return AgendaItemPresenter::redacted($item);
        }

        return [
            'id' => (string) $item->id,
            'title' => (string) ($item->title ?? ''),
            'type' => $item->type?->value,
            // The tooltip derives the item's status from these, as getAgendaItemStatus() does.
            'votes' => $item->outcomeVotes()
                ->map(fn (Vote $vote): array => $vote->only(['student_vote', 'decision', 'is_consensus']))
                ->values()
                ->all(),
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
            'recent_check_in' => (int) $statuses->get('recent_check_in', 0),
            'current' => $institutions->count() - $needsAttention - (int) $statuses->get('recent_check_in', 0),
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

        $activeUsers = (clone $query)
            ->select('id', 'name', 'email', 'profile_photo_path', 'last_action')
            ->where('last_action', '>=', $thirtyDaysAgo)
            ->orderByDesc('last_action')
            ->orderBy('name')
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
            'preview_users' => $activeUsers
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
        return User::query()
            ->whereHas('current_duties', fn (Builder $query) => $query
                ->where('duties.is_active', true)
                ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->whereIn('institution_id', RepStatisticsScope::institutions($tenantIds)->select('institutions.id')));
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array<string, \Closure>
     */
    private function representativeDutyRelations(array $tenantIds): array
    {
        return [
            'current_duties' => fn ($query) => $query->select('duties.id', 'duties.name', 'duties.institution_id')
                ->where('duties.is_active', true)
                ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
                ->whereIn('institution_id', RepStatisticsScope::institutions($tenantIds)->select('institutions.id'))
                ->with('institution:id,name,tenant_id'),
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
    private function countedInstitutions(Collection $institutions): Collection
    {
        return $institutions->filter(RepStatisticsScope::counts(...))->values();
    }
}
