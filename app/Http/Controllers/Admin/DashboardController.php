<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivityRequests\GetUserActivityRequests;
use App\Actions\GetFollowedInstitutions;
use App\Actions\GetRecentlyEditedRecords;
use App\Actions\GetUpcomingMeetingsForUser;
use App\Actions\SerializeReservationCart;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Requests\ShowAdminHomeRequest;
use App\Models\Calendar;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionRelationService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RepStatisticsScope;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends AdminController
{
    use ApiResponses;

    /** "Sekamos institucijos" rows before the list links to the full, filtered collection. */
    public const int FOLLOWED_PREVIEW_COUNT = 5;

    public function __construct(
        public Authorizer $authorizer,
        private readonly InstitutionActivityStatusService $activityStatusService,
    ) {}

    public function index(ShowAdminHomeRequest $request, GetUserActivityRequests $activityRequests)
    {
        $user = fn () => once(fn () => $request->user());
        $loadUserDuties = fn () => once(function () use ($user) {
            $u = $user();
            $u->loadMissing('current_duties.institution.tenant.primary_institution.media');

            return $u;
        });

        $heroImage = fn () => once(fn () => $loadUserDuties()->current_duties
            ->map(fn ($duty) => $duty->institution?->tenant?->primary_institution)
            ->filter()->unique('id')->lazy()
            ->map(fn (Institution $institution) => $institution->imageData('image'))
            ->first(fn ($image) => $image !== null));

        $currentInstitutionIds = fn () => once(fn () => $user()->relationLoaded('current_duties')
            ? $user()->current_duties->pluck('institution_id')->filter()->unique()
            : $user()->current_duties()->pluck('duties.institution_id')->filter()->unique());

        $taskStats = fn () => once(function () use ($user) {
            $counts = Task::openTaskCountsFor($user());

            return [
                'total' => $counts['tasks_count'],
                'overdue' => $counts['overdue_tasks_count'],
                'dueSoon' => $counts['due_soon_tasks_count'],
            ];
        });

        $upcomingTasks = fn () => once(fn () => $this->upcomingTasks($user()));

        $upcomingMeetings = fn () => once(fn () => GetUpcomingMeetingsForUser::execute($user()));

        // These panels share a deferred request to keep the attention queue available first.
        $secondary = 'secondary';

        $institutionsNeedingAttention = Inertia::defer(
            fn () => $this->institutionsNeedingAttention(
                $currentInstitutionIds()
            ),
            $secondary,
        );

        $upcomingCalendarEvents = Inertia::defer(
            fn () => $user()->can('viewAny', News::class) ? $this->upcomingCalendarEvents() : [],
            $secondary,
        );

        $latestNews = Inertia::defer(
            fn () => $user()->can('viewAny', News::class) ? $this->latestNews() : [],
            $secondary,
        );

        $recentlyEdited = Inertia::defer(
            fn () => GetRecentlyEditedRecords::execute($user())->all(),
            $secondary,
        );

        $followedInstitutions = Inertia::defer(
            fn () => GetFollowedInstitutions::execute($user(), self::FOLLOWED_PREVIEW_COUNT),
            $secondary,
        );

        return Inertia::render('Admin/ShowAdminHome', [
            'activeActivityRequests' => fn () => $activityRequests->activeSummary($user()),
            'actionWindowLaunch' => fn () => $request->actionWindowLaunch($user()),
            'taskStats' => fn () => $taskStats(),
            'upcomingTasks' => fn () => $upcomingTasks(),
            'upcomingMeetings' => fn () => $upcomingMeetings()['items'],
            'upcomingMeetingsTotal' => fn () => $upcomingMeetings()['total'],
            'heroImage' => function () use ($heroImage): ?array {
                $image = $heroImage();

                return $image === null ? null : ['url' => $image['url'], 'srcset' => $image['srcset'], 'focalPoint' => $image['focal_point']];
            },
            'institutionsNeedingAttention' => $institutionsNeedingAttention,
            'upcomingCalendarEvents' => $upcomingCalendarEvents,
            'latestNews' => $latestNews,
            'recentlyEdited' => $recentlyEdited,
            'followedInstitutions' => $followedInstitutions,
            'registrationForms' => fn () => $this->registrationForms($user()),
            'reservationDraft' => fn () => SerializeReservationCart::summary($user()),
        ]);
    }

    private function upcomingTasks(User $user): Collection
    {
        return $user->tasks()
            ->whereNull('completed_at')
            ->where('due_date', '<=', now()->addDays(14))
            ->orderBy('due_date')
            ->orderBy('tasks.id')
            ->with(['taskable' => fn (MorphTo $morphTo) => $morphTo->constrain([
                Institution::class => fn ($query) => $query->select('id', 'name'),
                Meeting::class => fn ($query) => $query->select('id', 'title'),
                Reservation::class => fn ($query) => $query->select('id', 'name'),
                User::class => fn ($query) => $query->select('id', 'name'),
            ])])
            ->take(3)
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'due_date' => $task->due_date?->toISOString(),
                'is_overdue' => $task->isOverdue(),
                'taskable_type' => $task->taskable_type ?? '',
                'taskable_id' => $task->taskable_id,
                'taskable' => $task->taskable === null ? null : [
                    'id' => (string) $task->taskable_id,
                    'name' => $task->taskable->getAttribute('title') ?? $task->taskable->getAttribute('name'),
                ],
                'action_type' => $task->action_type?->value,
            ]);
    }

    public function institutionGraph()
    {
        abort_unless($this->authorizer->allows(auth()->user(), 'institutions.read.padalinys'), 403);

        // Only the fields the graph actually renders (id, name, users_count, tenant for grouping).
        $institutions = Institution::withCount('users')->get(['id', 'name', 'tenant_id', 'is_active']);

        $relations = app(InstitutionRelationService::class);
        $graphs = Cache::remember('dashboard:institution-graphs:'.app()->getLocale().':'.InstitutionRelationService::version(), now()->addDay(), fn () => [
            'institutions' => $relations->institutionGraph(),
            'types' => $relations->typeGraph(),
        ]);

        return Inertia::render('Admin/ShowInstitutionGraph', [
            'institutions' => $institutions,
            'institutionRelationships' => $graphs['institutions'],
            'types' => $graphs['types']['nodes'],
            'typeRelationships' => $graphs['types']['edges'],
        ]);
    }

    /**
     * Institutions whose meeting cadence needs a nudge.
     *
     * @param  Collection<int, int|string>  $userInstitutionIds
     * @return array<int, array<string, mixed>>
     */
    private function institutionsNeedingAttention(Collection $userInstitutionIds): array
    {
        $userInstitutions = $this->activityStatusService->withSummary(Institution::query()
            ->whereIn('id', $userInstitutionIds))
            ->get()
            ->filter(RepStatisticsScope::counts(...));

        return $userInstitutions
            ->map(function (Institution $institution) {
                $activityStatus = $this->activityStatusService->resolve($institution);

                return [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'is_active' => (bool) $institution->is_active,
                    ...$activityStatus->toArray(),
                ];
            })
            ->filter(fn (array $status): bool => $status['requires_action'])
            ->sortByDesc('priority')
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * The shared registration forms (members, student reps) this user may open, for Sparčioji prieiga.
     *
     * @return array<int, array{key: string, href: string}>
     */
    private function registrationForms(User $user): array
    {
        $formSettings = app(FormSettings::class);
        $configured = array_filter([
            'member' => $formSettings->member_registration_form_id,
            'student_rep' => $formSettings->student_rep_registration_form_id,
        ]);

        if ($configured === []) {
            return [];
        }

        $forms = Form::query()->whereIn('id', $configured)->get()->keyBy('id');

        return collect($configured)
            ->map(fn (string $formId, string $key): ?array => ($form = $forms->get($formId)) !== null && $user->can('view', $form)
                ? ['key' => $key, 'href' => route('forms.show', $form)]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Upcoming published calendar events for the compact EventCard.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcomingCalendarEvents(): array
    {
        return Calendar::query()
            ->where('is_draft', false)
            ->where('date', '>=', now())
            ->orderBy('date')
            ->take(3)
            ->get(['id', 'title', 'date', 'end_date', 'is_all_day', 'permalink'])
            ->map(fn (Calendar $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->date->toISOString(),
                'end_date' => $event->end_date?->toISOString(),
                'is_all_day' => (bool) $event->is_all_day,
                'public_url' => $event->publicUrl(app()->getLocale()),
            ])
            ->all();
    }

    private function latestNews(): array
    {
        return News::query()
            ->where('draft', false)
            ->whereNotNull('publish_time')
            ->where('publish_time', '<=', now())
            ->where('lang', app()->getLocale())
            ->with(['tenant:id,shortname,alias', 'media'])
            ->orderByDesc('publish_time')
            ->take(3)
            ->get()
            ->map(fn (News $news) => [
                'id' => $news->id,
                'title' => $news->title,
                'permalink' => $news->permalink,
                'lang' => $news->lang,
                'publish_time' => $news->publish_time,
                'image' => $news->getImageUrl(),
                'public_url' => $news->publicUrl(),
            ])
            ->all();
    }
}
