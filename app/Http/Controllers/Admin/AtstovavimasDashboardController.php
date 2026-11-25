<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivityRequests\GetOpenActivityRequestSpans;
use App\Actions\GetFollowedInstitutions;
use App\Actions\GetUpcomingMeetingsForUser;
use App\Actions\GetUserCoordinators;
use App\Enums\InstitutionScope;
use App\Enums\TenantType;
use App\Http\Controllers\AdminController;
use App\Http\Requests\ShowAtstovavimasDashboardRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Institutions\AtstovavimasInstitutionPresenter;
use App\Services\Institutions\InstitutionQueries;
use App\Settings\AtstovavimasSettings;
use App\Settings\RepMetricSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AtstovavimasDashboardController extends AdminController
{
    public function atstovavimas(ShowAtstovavimasDashboardRequest $request): Response|RedirectResponse
    {
        $requestedScope = $request->validated('scope') ?? $request->validated('tab');

        if ($requestedScope === 'tenant' && Gate::allows('viewAny', Meeting::class)) {
            return redirect()->route('dashboard.atstovavimas.padaliniai');
        }

        // Get basic user info with duty institution IDs only
        $user = fn () => once(function () use ($request) {
            $u = $request->user();
            $u?->loadMissing(['current_or_upcoming_duties:id,name,institution_id']);

            return $u;
        });

        $userInstitutions = fn () => once(fn () => InstitutionQueries::representativeDashboardInstitutions($user()));
        $today = today();

        return Inertia::render('Admin/Dashboard/ShowAtstovavimas', [
            'user' => fn () => [
                ...$user()->withoutRelations()->toArray(),
                'current_or_upcoming_duties' => $user()->current_or_upcoming_duties->map(fn ($duty) => [
                    'id' => $duty->id,
                    'institution_id' => $duty->institution_id,
                ])->values(),
            ],
            'userInstitutions' => fn () => AtstovavimasInstitutionPresenter::rows($userInstitutions()),
            'mayHaveRelatedInstitutions' => fn () => $userInstitutions()->isNotEmpty(),
            'relatedInstitutions' => Inertia::optional(fn () => AtstovavimasInstitutionPresenter::rows(
                InstitutionQueries::relatedRepresentativeDashboardInstitutions(
                    InstitutionQueries::representativeDashboardInstitutions($user(), withSummary: false),
                ),
            ))->once(),
            'initialMeetingsWindow' => [
                'from' => $today->copy()->subMonthsNoOverflow(4)->toDateString(),
                'to' => $today->copy()->addMonthsNoOverflow(2)->toDateString(),
            ],
            'canViewTenantOverview' => fn () => Gate::allows('viewAny', Meeting::class),
            // Duty and followed institutions alike; the page narrows it by the tenant selector.
            'upcomingMeetings' => fn () => GetUpcomingMeetingsForUser::execute($user()),
            'followedInstitutions' => Inertia::defer(
                fn () => GetFollowedInstitutions::execute($user(), DashboardController::FOLLOWED_PREVIEW_COUNT),
                'secondary',
            ),
            // Open requests over the user's own bodies, drawn on the timeline.
            'activityRequests' => Inertia::defer(fn () => $this->ownActivityRequests($user()), 'secondary'),
            // R-g: the human answer to "I'm stuck" belongs on every rep screen, but never on the first paint.
            'coordinators' => Inertia::defer(fn () => GetUserCoordinators::execute($user()), 'secondary'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ownActivityRequests(User $user): array
    {
        $institutionIds = $user->current_or_upcoming_duties->pluck('institution_id')
            ->merge($user->secretariedInstitutions()->pluck('institutions.id'))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        return app(GetOpenActivityRequestSpans::class)->execute($user, $institutionIds);
    }

    /**
     * The padaliniai overview: every admin gets the Gantt of public meetings and their own bodies;
     * the statistics are only for the padaliniai the user manages. Data loads through the admin API.
     */
    #[Authorize('viewAny', Meeting::class)]
    public function padaliniai(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $statsTenants = self::tenantOptions(app(AtstovavimasSettings::class)->getVisibleTenantIds($user));
        $ganttTenants = self::tenantOptions(Tenant::query()->representational()->pluck('id'));

        return Inertia::render('Admin/Dashboard/ShowAtstovavimasPadaliniai', [
            'statsTenants' => $statsTenants,
            'ganttTenants' => $ganttTenants,
            'defaultGanttTenantIds' => $this->defaultGanttTenantIds($user, $statsTenants, $ganttTenants),
            'canViewTenantTasks' => $user->can('viewAny', Task::class),
            'canAskAboutActivity' => $user->can('viewAny', InstitutionCheckIn::class),
            'countedScopes' => array_map(
                fn (InstitutionScope $scope): string => $scope->label(app()->getLocale()),
                app(RepMetricSettings::class)->countedInstitutionScopes(),
            ),
        ]);
    }

    /**
     * A coordinator starts on the padaliniai they manage, a rep on those of their own bodies.
     *
     * @param  SupportCollection<int, array{id: int, shortname: string, type: TenantType|null}>  $statsTenants
     * @param  SupportCollection<int, array{id: int, shortname: string, type: TenantType|null}>  $ganttTenants
     * @return list<string>
     */
    private function defaultGanttTenantIds(User $user, SupportCollection $statsTenants, SupportCollection $ganttTenants): array
    {
        $tenantIds = $statsTenants->isNotEmpty()
            ? $statsTenants->pluck('id')
            : Institution::query()->whereIn('id', $user->current_or_upcoming_duties()->pluck('institution_id'))->pluck('tenant_id');

        return $tenantIds
            ->intersect($ganttTenants->pluck('id'))
            ->unique()
            ->map(fn ($tenantId) => (string) $tenantId)
            ->values()
            ->all();
    }

    /**
     * @param  SupportCollection<int, int>  $tenantIds
     * @return SupportCollection<int, array{id: int, shortname: string, type: TenantType|null}>
     */
    public static function tenantOptions(SupportCollection $tenantIds): SupportCollection
    {
        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->representational()
            ->orderBy('shortname_vu')
            ->get(['id', 'shortname', 'type'])
            ->map(function (Tenant $tenant): array {
                $shortname = __($tenant->shortname);

                return [
                    'id' => $tenant->id,
                    'shortname' => is_string($shortname) ? $shortname : $tenant->shortname,
                    'type' => $tenant->type,
                ];
            });
    }
}
