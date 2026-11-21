<?php

namespace App\Services\Institutions;

use App\Enums\TenantType;
use App\Models\Activity;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionRelationService;
use App\Services\Meetings\IncompleteMeetingQueries;
use App\Services\ModelAuthorizer;
use App\Settings\AtstovavimasSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InstitutionQueries
{
    /** Duty upserts use creation permissions, while the dashboard also includes memberships. */
    public static function getInstitutionsForUpserts(User $actor, ModelAuthorizer $authorizer)
    {
        // Check for global access
        $hasGlobalAccess = $authorizer->allows($actor, 'duties.create.*');

        // Get tenant IDs where user can create duties
        $tenantIds = $hasGlobalAccess
            ? collect()
            : $authorizer->tenants($actor, 'duties.create.padalinys')->pluck('id');

        return Institution::select('id', 'name', 'alias', 'tenant_id', 'is_active')
            ->when(! $hasGlobalAccess, function ($query) use ($tenantIds): void {
                // Only show institutions from tenants where user has permission
                $query->whereIn('tenant_id', $tenantIds);
            })
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with('tenant:id,shortname')
            ->get();
    }

    /** Includes institutions in visible tenants and institutions where the actor holds a non-ended duty. */
    public static function getInstitutionsForDashboard(User $actor, ModelAuthorizer $authorizer)
    {
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($actor);
        $userInstitutionIds = $actor->current_or_upcoming_duties()
            ->pluck('institution_id')
            ->filter()
            ->unique();

        return Institution::select('id', 'name', 'alias', 'tenant_id', 'is_active')->active()
            ->when($visibleTenantIds->isNotEmpty(), function ($query) use ($visibleTenantIds, $userInstitutionIds): void {
                $query->where(function ($q) use ($visibleTenantIds, $userInstitutionIds): void {
                    $q->whereIn('tenant_id', $visibleTenantIds);

                    if ($userInstitutionIds->isNotEmpty()) {
                        $q->orWhereIn('id', $userInstitutionIds);
                    }
                });
            }, function ($query) use ($userInstitutionIds): void {
                $query->whereIn('id', $userInstitutionIds);
            })
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with([
                'tenant:id,shortname',
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time,type',
                'meetings.agendaItems:id,meeting_id,title,type,brought_by_students,order,is_private,public_title',
                'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main,is_consensus',
                'meetings.calendarEvent:id,meeting_id,is_draft',
                // Historical assignments are required for Gantt coverage periods.
                'duties.users:id,name,profile_photo_path,last_action',
                'duties.types:id,title',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query): void {
                    $query->where('start_time', '>', now());
                },
            ])
            ->tap(self::withActivityDates(...))
            ->afterQuery(self::hideMemberDetails(...))
            ->get();
    }

    /** @return EloquentCollection<int, Institution> */
    public static function getUserInstitutionsForDashboard(User $actor, bool $activeOnly = false, ?CarbonInterface $meetingsFrom = null): EloquentCollection
    {
        return self::assignedInstitutions($actor, self::buildInstitutionQuery($meetingsFrom)
            ->when($activeOnly, fn ($query) => $query->active()));
    }

    /** @return EloquentCollection<int, Institution> */
    public static function representativeDashboardInstitutions(User $actor, bool $withSummary = true): EloquentCollection
    {
        $query = $withSummary ? self::representativeDashboardQuery() : Institution::select('id');

        return self::assignedInstitutions($actor, $query->active()
            ->whereHas('tenant', fn ($tenant) => $tenant->whereIn('type', TenantType::representationalValues())));
    }

    /**
     * @param  EloquentCollection<int, Institution>  $own
     * @return EloquentCollection<int, Institution>
     */
    public static function relatedRepresentativeDashboardInstitutions(EloquentCollection $own, bool $withSummary = true): EloquentCollection
    {
        $relations = app(InstitutionRelationService::class);
        $ownIds = $own->modelKeys();
        $picked = [];

        foreach ($ownIds as $sourceId) {
            foreach ($relations->relatedTo($sourceId) as $entry) {
                $id = $entry['institution_id'];

                if (in_array($id, $ownIds, true) || (! $entry['authorized'] && isset($picked[$id]))) {
                    continue;
                }

                $picked[$id] = [...$entry, 'source_institution_id' => $sourceId];
            }
        }

        if ($picked === []) {
            return new EloquentCollection;
        }

        $query = $withSummary ? self::representativeDashboardQuery() : Institution::select('id');

        return $query->active()->whereKey(array_keys($picked))->get()
            ->sortByDesc(fn (Institution $institution) => $picked[$institution->id]['authorized'])
            ->values()
            ->each(function (Institution $institution) use ($picked): void {
                $entry = $picked[$institution->id];
                $institution->setAttribute('is_related', true);
                $institution->setAttribute('authorized', $entry['authorized']);
                $institution->setAttribute('relationship_direction', $entry['direction']);
                $institution->setAttribute('relationship_type', $entry['via']);
                $institution->setAttribute('source_institution_id', $entry['source_institution_id']);
            });
    }

    /**
     * @param  Builder<Institution>  $query
     * @return EloquentCollection<int, Institution>
     */
    private static function assignedInstitutions(User $actor, Builder $query): EloquentCollection
    {
        $dutyInstitutionIds = $actor->current_or_upcoming_duties()
            ->pluck('institution_id')
            ->filter()
            ->unique();

        $secretariedInstitutionIds = $actor->secretariedInstitutions()
            ->pluck('institutions.id')
            ->unique();

        $userInstitutionIds = $dutyInstitutionIds->merge($secretariedInstitutionIds)->unique();

        if ($userInstitutionIds->isEmpty()) {
            return new EloquentCollection;
        }

        $institutions = $query->whereIn('id', $userInstitutionIds)->get();

        $institutions->each(function (Institution $institution) use ($dutyInstitutionIds, $secretariedInstitutionIds): void {
            $isSecretary = ! $dutyInstitutionIds->contains($institution->id)
                && $secretariedInstitutionIds->contains($institution->id);

            $institution->setAttribute('is_secretary', $isSecretary);
            $institution->setAttribute('is_administered', $isSecretary);
        });

        return $institutions;
    }

    /** @return Builder<Institution> */
    private static function representativeDashboardQuery(): Builder
    {
        $query = Institution::select('id', 'name', 'short_name', 'tenant_id', 'meeting_periodicity_days');

        return app(InstitutionActivityStatusService::class)->withSummary($query)
            ->with([
                'tenant:id,shortname',
                'duties:id,institution_id',
                'duties.users:id,name,profile_photo_path',
                'checkIns:id,institution_id,start_date,end_date,note',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => fn ($meetings) => $meetings->where('start_time', '>', now()),
                'meetings as incomplete_meetings_count' => IncompleteMeetingQueries::constrain(...),
            ])
            ->tap(self::withLastActivityDate(...));
    }

    /** @param  Collection|array  $tenantIds */
    public static function getInstitutionsForTenants(User $actor, $tenantIds, ModelAuthorizer $authorizer)
    {
        $tenantIds = collect($tenantIds)->filter();

        if ($tenantIds->isEmpty()) {
            return collect();
        }

        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($actor);

        // Filter to only accessible tenants
        $accessibleTenantIds = $tenantIds->intersect($visibleTenantIds);

        if ($accessibleTenantIds->isEmpty()) {
            return collect();
        }

        return self::buildInstitutionQuery()->active()
            ->whereIn('tenant_id', $accessibleTenantIds)
            ->get();
    }

    /**
     * Loads only the relations needed for activity statuses; the timeline fetches meetings separately.
     *
     * @param  Collection|array  $tenantIds
     */
    public static function getTimelineInstitutionsForTenants(User $actor, $tenantIds, ModelAuthorizer $authorizer)
    {
        $tenantIds = collect($tenantIds)->filter();

        if ($tenantIds->isEmpty()) {
            return collect();
        }

        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($actor);

        // Filter to only accessible tenants
        $accessibleTenantIds = $tenantIds->intersect($visibleTenantIds);

        if ($accessibleTenantIds->isEmpty()) {
            return collect();
        }

        return self::timelineInstitutionsQuery()->active()
            ->whereIn('tenant_id', $accessibleTenantIds)
            ->get();
    }

    /**
     * The timeline rows for explicit institution ids — the Gantt's own set, which mixes whole
     * padaliniai with single public, own and related institutions.
     *
     * @param  Collection<int, string>  $institutionIds
     * @return EloquentCollection<int, Institution>
     */
    public static function getTimelineInstitutions(Collection $institutionIds)
    {
        if ($institutionIds->isEmpty()) {
            return new EloquentCollection;
        }

        return self::timelineInstitutionsQuery()->active()
            ->whereIn('institutions.id', $institutionIds)
            ->get();
    }

    /**
     * @return Builder<Institution>
     */
    private static function timelineInstitutionsQuery()
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id', 'is_active', 'meeting_periodicity_days')
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with([
                'tenant:id,shortname,type',
                'types', // needed for has_public_meetings and meeting_periodicity_days
                // Meetings only carry start_time: enough for activity-status resolution.
                'meetings:id,start_time',
                // Historical assignments are required for Gantt coverage periods.
                'duties.users:id,name,profile_photo_path,last_action',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query): void {
                    $query->where('start_time', '>', now());
                },
            ])
            ->tap(self::withActivityDates(...))
            ->afterQuery(self::hideMemberDetails(...));
    }

    /** @return Builder<Institution> */
    private static function buildInstitutionQuery(?CarbonInterface $meetingsFrom = null)
    {
        return Institution::select('id', 'name', 'alias', 'tenant_id', 'is_active', 'meeting_periodicity_days')
            ->whereHas('tenant', function ($query): void {
                $query->whereIn('type', TenantType::representationalValues());
            })
            ->with([
                'tenant:id,shortname,type',
                'types', // explicit since not auto-loaded
                'meetings' => fn ($query) => $query->select('meetings.id', 'title', 'start_time', 'type')
                    ->when($meetingsFrom, fn ($query) => $query->where('start_time', '>=', $meetingsFrom)),
                'meetings.institutions:id',
                'meetings.institutions.types',
                'meetings.agendaItems:id,meeting_id,title,type,brought_by_students,order,is_private,public_title',
                'meetings.agendaItems.votes:id,agenda_item_id,title,decision,student_vote,student_benefit,is_main,is_consensus',
                // Load fileableFiles for has_report and has_protocol accessors (prevents N+1)
                'meetings.fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                // Same reason for has_calendar_event, which the Gantt tooltip reads.
                'meetings.calendarEvent:id,meeting_id,is_draft',
                // Historical assignments are required for Gantt coverage periods.
                'duties.users:id,name,profile_photo_path,last_action',
                'duties.types:id,title',
                'checkIns',
            ])
            ->withCount([
                'meetings as upcoming_meetings_count' => function ($query): void {
                    $query->where('start_time', '>', now());
                },
            ])
            ->tap(self::withActivityDates(...))
            ->afterQuery(self::hideMemberDetails(...));
    }

    /**
     * The timeline needs who held a seat and when. Contact details, notes and login times stay on
     * the server, even for a related institution the viewer cannot open.
     *
     * @param  EloquentCollection<int, Institution>  $institutions
     */
    public static function hideMemberDetails(EloquentCollection $institutions): void
    {
        foreach ($institutions as $institution) {
            foreach ($institution->relationLoaded('duties') ? $institution->duties : [] as $duty) {
                foreach ($duty->relationLoaded('users') ? $duty->users : [] as $user) {
                    $user->setVisible(['id', 'name', 'profile_photo_path', 'activity_category', 'pivot']);
                    $user->getRelation('pivot')?->setVisible(['start_date', 'end_date']);
                }
            }
        }
    }

    /**
     * @param  Builder<Institution>  $query
     */
    private static function withActivityDates(Builder $query): void
    {
        $meetingsOfInstitution = fn () => Meeting::query()
            ->join('institution_meeting', 'meetings.id', '=', 'institution_meeting.meeting_id')
            ->whereColumn('institution_meeting.institution_id', 'institutions.id')
            ->limit(1);

        $query->tap(self::withLastActivityDate(...))->addSelect([
            'last_meeting_date' => $meetingsOfInstitution()->select('start_time')
                ->where('start_time', '<=', now())
                ->orderByDesc('start_time'),
            'next_meeting_date' => $meetingsOfInstitution()->select('start_time')
                ->where('start_time', '>', now())
                ->orderBy('start_time'),
        ])->afterQuery(function (EloquentCollection $institutions): void {
            foreach ($institutions as $institution) {
                $institution->setAttribute('last_meeting_date', self::activityDateToIso($institution->getAttribute('last_meeting_date')));
                $institution->setAttribute('next_meeting_date', self::activityDateToIso($institution->getAttribute('next_meeting_date')));
            }
        });
    }

    /**
     * Uses logged changes and check-ins because migrations can bump meetings.updated_at.
     *
     * @param  Builder<Institution>  $query
     */
    private static function withLastActivityDate(Builder $query): void
    {
        $query->addSelect([
            'meeting_activity_at' => Activity::query()->select('activity_log.created_at')
                ->join('institution_meeting', 'institution_meeting.meeting_id', '=', 'activity_log.root_subject_id')
                ->where('activity_log.root_subject_type', 'meeting')
                ->whereColumn('institution_meeting.institution_id', 'institutions.id')
                ->whereNotNull('activity_log.causer_id')
                ->orderByDesc('activity_log.created_at')
                ->limit(1),
            'check_in_activity_at' => InstitutionCheckIn::select('created_at')
                ->whereColumn('institution_check_ins.institution_id', 'institutions.id')
                ->orderByDesc('created_at')
                ->limit(1),
        ])->afterQuery(function (EloquentCollection $institutions): void {
            $parts = ['meeting_activity_at', 'check_in_activity_at'];

            /** @var EloquentCollection<int, Institution> $institutions */
            $institutions->each(function (Institution $institution) use ($parts): void {
                $latest = collect($parts)->map(fn (string $part) => $institution->getAttribute($part))->filter()->max();
                $institution->setAttribute('last_activity_at', self::activityDateToIso($latest));

                foreach ($parts as $part) {
                    $institution->offsetUnset($part);
                }
            });
        });
    }

    private static function activityDateToIso(mixed $value): ?string
    {
        return is_string($value) ? Carbon::parse($value)->toISOString() : null;
    }
}
