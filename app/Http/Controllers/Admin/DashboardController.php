<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Settings\MeetingSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends AdminController
{
    use ApiResponses;

    public function __construct(
        public Authorizer $authorizer,
        private readonly InstitutionActivityStatusService $activityStatusService,
    ) {}

    public function index(Request $request)
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $userTenantId = $user->current_duties->first()?->institution?->tenant_id;

        // Get notification count
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        // Get task statistics for the dashboard
        $taskStats = [
            'total' => $user->tasks()->whereNull('completed_at')->count(),
            'overdue' => $user->tasks()->whereNull('completed_at')->where('due_date', '<', now())->count(),
            'dueSoon' => $user->tasks()->whereNull('completed_at')
                ->where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays(7))
                ->count(),
        ];

        // Get upcoming tasks (due within 14 days or overdue)
        $upcomingTasks = $user->tasks()
            ->whereNull('completed_at')
            ->where(function ($query): void {
                $query->where('due_date', '<=', now()->addDays(14))
                    ->orWhere('due_date', '<', now());
            })
            ->orderByRaw('CASE WHEN due_date < ? THEN 0 ELSE 1 END', [now()]) // Overdue first
            ->orderBy('due_date')
            ->with('taskable')
            ->take(10)
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'name' => $task->name,
                'due_date' => $task->due_date?->toISOString(),
                'is_overdue' => $task->isOverdue(),
                'taskable_type' => $task->taskable_type ?? '',
                'taskable_id' => $task->taskable_id,
                'action_type' => $task->action_type?->value,
                'metadata' => $task->metadata,
                'progress' => $task->getProgress(),
                'can_be_manually_completed' => $task->canBeManuallyCompleted(),
                'icon' => $task->icon,
                'color' => $task->color,
            ]);

        // Get user's institutions and upcoming meetings
        $userInstitutionIds = $user->current_duties->pluck('institution_id')->filter()->unique();

        $upcomingMeetings = Meeting::query()
            ->whereHas('institutions', fn ($q) => $q->whereIn('institutions.id', $userInstitutionIds))
            ->where('start_time', '>=', now()->startOfDay())
            ->where('start_time', '<', now()->addMonths(2))
            ->orderBy('start_time')
            ->with(['institutions:id,name'])
            ->take(3)
            ->get()
            ->map(fn ($meeting) => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'start_time' => $meeting->start_time->toISOString(),
                'institution_name' => $meeting->institutions->first()?->name,
            ]);

        // Get institutions needing attention (overdue meetings based on periodicity)
        $meetingSettings = app(MeetingSettings::class);
        $excludedTypeIds = $meetingSettings->getExcludedInstitutionTypeIds();
        $userInstitutions = Institution::query()
            ->whereIn('id', $userInstitutionIds)
            ->with([
                'meetings:id,start_time',
                'types',
                // Load all check-ins: InstitutionActivityStatusService::resolve() needs
                // completed check-ins to compute lastActivityAt, and it calls loadMissing()
                // which is a no-op once the relation is already loaded. Filtering here
                // would silently hide historical activity and skew the status, causing the
                // card to disagree with the ShowAtstovavimas page.
                'checkIns',
            ])
            ->get()
            ->filter(function ($institution) use ($excludedTypeIds) {
                // Exclude institutions with excluded types
                if ($excludedTypeIds->isNotEmpty()) {
                    return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
                }

                return true;
            });

        $institutionsNeedingAttention = $userInstitutions
            ->map(function (Institution $institution) {
                $activityStatus = $this->activityStatusService->resolve($institution);

                return [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    ...$activityStatus->toArray(),
                ];
            })
            ->filter(fn (array $status): bool => $status['requires_action'])
            ->sortByDesc('priority')
            ->take(3)
            ->values();

        // Get upcoming calendar events (non-draft, future) - return full models for EventCard component
        $upcomingCalendarEvents = Calendar::query()
            ->where('is_draft', false)
            ->where('date', '>=', now())
            ->with(['tenant:id,shortname', 'category:id,name'])
            ->orderBy('date')
            ->take(3)
            ->get();

        // Get latest published news - return full models for NewsCard component
        $locale = app()->getLocale();
        $latestNews = News::query()
            ->where('draft', false)
            ->whereNotNull('publish_time')
            ->where('publish_time', '<=', now())
            ->where('lang', $locale)
            ->with(['tenant:id,shortname,alias'])
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
                'tenant' => $news->tenant,
            ]);

        return $this->inertiaResponse('Admin/ShowAdminHome', [
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'hasNotifications' => $unreadNotificationsCount > 0,
            'taskStats' => $taskStats,
            'upcomingTasks' => $upcomingTasks,
            'upcomingMeetings' => $upcomingMeetings,
            'institutionsNeedingAttention' => $institutionsNeedingAttention,
            'upcomingCalendarEvents' => $upcomingCalendarEvents,
            'latestNews' => $latestNews,
        ]);
    }

    public function institutionGraph()
    {
        // Only the fields the graph actually renders (id, name, users_count, tenant for grouping).
        $institutions = Institution::withCount('users')->get(['id', 'name', 'tenant_id']);

        $typeGraph = RelationshipService::getTypeRelationshipGraph();

        return $this->inertiaResponse('Admin/ShowInstitutionGraph', [
            'institutions' => $institutions,
            'institutionRelationships' => RelationshipService::getAllRelatedInstitutionsEnriched(),
            'types' => $typeGraph['nodes'],
            'typeRelationships' => $typeGraph['edges'],
        ]);
    }
}
