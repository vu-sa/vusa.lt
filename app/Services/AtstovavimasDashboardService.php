<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\User;
use App\Services\ResourceServices\DutyService;
use App\Settings\MeetingSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AtstovavimasDashboardService
{
    private const REPRESENTATIVE_PREVIEW_LIMIT = 4;

    public function __construct(
        private readonly InstitutionActivityStatusService $activityStatusService,
        private readonly ModelAuthorizer $authorizer,
        private readonly MeetingSettings $meetingSettings,
    ) {}

    /**
     * @param  list<int>  $tenantIds
     * @return array{
     *   institutions: Collection<int, Institution>,
     *   related_institutions: Collection<int, Institution>,
     *   institution_summary: array<string, int>,
     *   representative_activity: array<string, mixed>
     * }
     */
    public function tenantTimeline(array $tenantIds): array
    {
        $institutions = DutyService::getInstitutionsForTenants($tenantIds, $this->authorizer);
        $institutions = $this->withoutExcludedInstitutionTypes($institutions);
        $this->decorateInstitutions($institutions);

        $relatedInstitutions = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection($institutions->all())
        );
        $relatedInstitutions = $this->withoutExcludedInstitutionTypes($relatedInstitutions);
        $this->decorateInstitutions($relatedInstitutions);

        return [
            'institutions' => $institutions->values(),
            'related_institutions' => $relatedInstitutions->values(),
            'institution_summary' => $this->institutionSummary($institutions),
            'representative_activity' => $this->representativeActivitySummary($tenantIds),
        ];
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
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('current_duties', function (Builder $dutyQuery) use ($search) {
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
        $institutions->each(function (Institution $institution) {
            $isAuthorized = $institution->getAttribute('authorized') !== false;

            $institution->meetings->each(function ($meeting) use ($isAuthorized) {
                $meeting->append($isAuthorized
                    ? ['completion_status', 'has_report', 'has_protocol']
                    : ['has_report', 'has_protocol']);
            });

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

            $institution->duties->each(function ($duty) {
                $duty->users->each(function (User $representative) {
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
            ->whereHas('current_duties', function (Builder $query) use ($tenantIds, $excludedTypeIds) {
                $query->whereHas('institution', function (Builder $institutionQuery) use ($tenantIds, $excludedTypeIds) {
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
            'current_duties' => function ($query) use ($tenantIds, $excludedTypeIds) {
                $query->select('duties.id', 'duties.name', 'duties.institution_id')
                    ->whereHas('institution', function (Builder $institutionQuery) use ($tenantIds, $excludedTypeIds) {
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
