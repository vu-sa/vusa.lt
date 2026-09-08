<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionScopeResolver;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\DutyService;
use App\Settings\AtstovavimasSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AtstovavimasDashboardController extends AdminController
{
    public function __construct(
        public Authorizer $authorizer,
        private readonly InstitutionActivityStatusService $activityStatusService,
    ) {}

    public function atstovavimas()
    {
        // Get basic user info with duty institution IDs only
        $user = User::query()->where('id', Auth::id())
            ->with(['current_duties:id,name,institution_id'])
            ->first();

        // Pre-load user's subscription data (followed and muted institution IDs)
        $followedInstitutionIds = $user->followedInstitutions()->pluck('institutions.id');
        $mutedInstitutionIds = $user->mutedInstitutions()->pluck('institutions.id');

        // Get only user's directly assigned institutions (lightweight, always loaded)
        $userInstitutions = DutyService::getUserInstitutionsForDashboard();

        // Helper function to append computed attributes to institutions
        $appendInstitutionAttributes = function ($institutions, $userInstitutionIds = null) use ($followedInstitutionIds, $mutedInstitutionIds) {
            $institutions->each(function ($institution) use ($userInstitutionIds, $followedInstitutionIds, $mutedInstitutionIds): void {
                $institution->meetings?->each->append(['completion_status', 'has_report', 'has_protocol', 'has_calendar_event']);
                // VU SA's own bodies are drawn like any other and hidden behind the chart's
                // own toggle; they used to be dropped here, which left the chart incomplete
                // with no way to ask for the rest.
                $institution->setAttribute(
                    'is_internal',
                    app(InstitutionScopeResolver::class)->forInstitution($institution)->isInternal()
                );
                // Add active_check_in from already-loaded checkIns
                $institution->active_check_in = $institution->checkIns
                    ?->where('end_date', '>=', now())
                    ->where('start_date', '<=', now())
                    ->first() ?? null;
                // Append has_public_meetings for UI indicators (uses already-loaded types relation)
                $institution->append('has_public_meetings');
                // Append meeting_periodicity_days for overdue warnings (inherits from types, defaults to 30)
                $institution->append('meeting_periodicity_days');
                $institution->setAttribute(
                    'activity_status',
                    $this->activityStatusService->resolve($institution)->toArray()
                );

                // Add subscription status for follow/mute UI
                $institution->subscription = [
                    'is_followed' => $followedInstitutionIds->contains($institution->id),
                    'is_muted' => $mutedInstitutionIds->contains($institution->id),
                    'is_duty_based' => $userInstitutionIds?->contains($institution->id) ?? false,
                ];
            });

            return $institutions;
        };

        // Get user's duty-based institution IDs for subscription status
        $userDutyInstitutionIds = $userInstitutions->pluck('id');

        // Append computed attributes to user institutions (all duty-based for user's own institutions)
        $appendInstitutionAttributes($userInstitutions, $userDutyInstitutionIds);

        // Get available tenants for filtering - only for coordinators and admins
        // Regular users should not see the tenant tab (they only see their assigned institutions)
        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $visibleTenantIds = $atstovavimasSettings->getVisibleTenantIds($user);

        if ($visibleTenantIds->isNotEmpty()) {
            $availableTenants = Tenant::query()
                ->whereIn('id', $visibleTenantIds)
                ->representational()
                ->orderBy('shortname_vu')
                ->get(['id', 'shortname', 'type'])
                ->map(fn ($tenant) => [
                    'id' => $tenant->id,
                    'shortname' => __($tenant->shortname),
                    'type' => $tenant->type,
                ]);
        } else {
            $availableTenants = collect();
        }

        // Quick check if user might have related institutions (without loading them)
        // This enables the filter UI even when relatedInstitutions is lazy-loaded
        $mayHaveRelatedInstitutions = $userInstitutions->isNotEmpty();

        return $this->inertiaResponse('Admin/Dashboard/ShowAtstovavimas', [
            // User with institutions - always included, even in partial reloads (ensures check-in data stays fresh)
            'user' => Inertia::always(fn () => [
                ...$user->toArray(),
                'current_duties' => $user->current_duties->map(function ($duty) use ($userInstitutions) {
                    $institution = $userInstitutions->firstWhere('id', $duty->institution_id);

                    return [
                        ...$duty->toArray(),
                        'institution' => $institution,
                    ];
                }),
            ]),
            // User's own institutions - always included, even in partial reloads (ensures check-in data stays fresh)
            'userInstitutions' => Inertia::always($userInstitutions->values()),
            // Quick flag to show/hide related institutions filter (lazy data may not be loaded yet)
            'mayHaveRelatedInstitutions' => $mayHaveRelatedInstitutions,
            // Lazy load relatedInstitutions - only fetched when explicitly requested via Inertia reload
            'relatedInstitutions' => Inertia::optional(function () use ($userInstitutions, $userDutyInstitutionIds, $followedInstitutionIds, $mutedInstitutionIds) {
                /** @var Collection<int, Institution> $institutionCollection */
                $institutionCollection = new Collection($userInstitutions->values()->all());
                $relatedInstitutions = RelationshipService::getRelatedInstitutionsForMultiple(
                    $institutionCollection
                );

                // Append computed attributes to related institution meetings
                // Note: For unauthorized institutions, we skip completion_status as it triggers N+1 agendaItems load
                $relatedInstitutions->each(function ($institution) use ($userDutyInstitutionIds, $followedInstitutionIds, $mutedInstitutionIds): void {
                    /** @var Institution&object{authorized?: bool, subscription?: array<string, bool>} $institution */
                    $isAuthorized = ($institution->authorized ?? true) !== false;
                    $institution->meetings->each(function ($meeting) use ($isAuthorized): void {
                        // Only append completion_status for authorized institutions (it lazy-loads agendaItems)
                        if ($isAuthorized) {
                            $meeting->append(['completion_status', 'has_report', 'has_protocol']);
                        } else {
                            $meeting->append(['has_report', 'has_protocol']);
                        }
                    });
                    $institution->append('has_public_meetings');
                    $institution->append('meeting_periodicity_days');
                    $institution->setAttribute(
                        'activity_status',
                        $this->activityStatusService->resolve($institution)->toArray()
                    );

                    // Add subscription status for related institutions
                    // @phpstan-ignore property.notFound
                    $institution->subscription = [
                        'is_followed' => $followedInstitutionIds->contains($institution->id),
                        'is_muted' => $mutedInstitutionIds->contains($institution->id),
                        'is_duty_based' => $userDutyInstitutionIds->contains($institution->id),
                    ];
                });

                return $relatedInstitutions->values();
            })->once(),
            'availableTenants' => $availableTenants,
            // Note: recentMeetings is fetched via API endpoint: api.v1.admin.meetings.recent
        ]);
    }
}
