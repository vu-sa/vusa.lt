<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Requests\UpdatePasswordRequest;
use App\Mail\FeedbackMail;
use App\Mail\NotificationDigest;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TestPushNotification;
use App\Services\AcademicCalendarService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\DutyService;
use App\Settings\AtstovavimasSettings;
use App\Settings\MeetingSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class DashboardController extends AdminController
{
    use ApiResponses;

    public function __construct(public Authorizer $authorizer) {}

    public function index(Request $request)
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $userTenantId = $user->current_duties->first()?->institution?->tenant_id;

        // TODO: for some reasoning, the chaining doesn't work
        $this->authorizer = $this->authorizer->forUser($user);

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
            ->where(function ($query) {
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
                'taskable_type' => class_basename($task->taskable_type ?? ''),
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
        $academicCalendar = app(AcademicCalendarService::class);

        $userInstitutions = Institution::query()
            ->whereIn('id', $userInstitutionIds)
            ->with(['meetings' => fn ($q) => $q->orderByDesc('start_time')->take(1), 'types'])
            ->get()
            ->filter(function ($institution) use ($excludedTypeIds) {
                // Exclude institutions with excluded types
                if ($excludedTypeIds->isNotEmpty()) {
                    return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
                }

                return true;
            });

        $institutionsNeedingAttention = $userInstitutions
            ->map(function ($institution) use ($academicCalendar) {
                $lastMeeting = $institution->meetings->first();
                $periodicity = $institution->meeting_periodicity_days ?? 30;

                if (! $lastMeeting) {
                    return [
                        'id' => $institution->id,
                        'name' => $institution->name,
                        'days_since_last_meeting' => null,
                        'periodicity' => $periodicity,
                        'status' => 'no_meetings',
                    ];
                }

                $daysSinceLastMeeting = $academicCalendar->effectiveDaysBetween($lastMeeting->start_time, now());
                $isOverdue = $daysSinceLastMeeting > $periodicity;
                $isApproaching = ! $isOverdue && $daysSinceLastMeeting >= ($periodicity * 0.8);

                if (! $isOverdue && ! $isApproaching) {
                    return null;
                }

                return [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'days_since_last_meeting' => $daysSinceLastMeeting,
                    'periodicity' => $periodicity,
                    'status' => $isOverdue ? 'overdue' : 'approaching',
                    'last_meeting_date' => $lastMeeting->start_time->toISOString(),
                ];
            })
            ->filter()
            ->sortByDesc(fn ($i) => ($i['days_since_last_meeting'] ?? 999) - $i['periodicity'])
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

        // Filter out institutions with excluded types (e.g., padalinys, pkp - institutions that don't have formal meetings)
        $excludedTypeIds = app(MeetingSettings::class)->getExcludedInstitutionTypeIds();
        if ($excludedTypeIds->isNotEmpty()) {
            $userInstitutions = $userInstitutions->filter(function ($institution) use ($excludedTypeIds) {
                // Exclude institution if any of its types are in the excluded list
                return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
            })->values();
        }

        // Helper function to append computed attributes to institutions
        $appendInstitutionAttributes = function ($institutions, $userInstitutionIds = null) use ($followedInstitutionIds, $mutedInstitutionIds) {
            $institutions->each(function ($institution) use ($userInstitutionIds, $followedInstitutionIds, $mutedInstitutionIds) {
                $institution->meetings?->each->append(['completion_status', 'has_report', 'has_protocol']);
                // Add active_check_in from already-loaded checkIns
                $institution->active_check_in = $institution->checkIns
                    ?->where('end_date', '>=', now())
                    ->where('start_date', '<=', now())
                    ->first() ?? null;
                // Append has_public_meetings for UI indicators (uses already-loaded types relation)
                $institution->append('has_public_meetings');
                // Append meeting_periodicity_days for overdue warnings (inherits from types, defaults to 30)
                $institution->append('meeting_periodicity_days');

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
                ->where('type', '!=', 'pkp')
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
                $relatedInstitutions->each(function ($institution) use ($userDutyInstitutionIds, $followedInstitutionIds, $mutedInstitutionIds) {
                    /** @var Institution&object{authorized?: bool, subscription?: array<string, bool>} $institution */
                    $isAuthorized = ($institution->authorized ?? true) !== false;
                    $institution->meetings->each(function ($meeting) use ($isAuthorized) {
                        // Only append completion_status for authorized institutions (it lazy-loads agendaItems)
                        if ($isAuthorized) {
                            $meeting->append(['completion_status', 'has_report', 'has_protocol']);
                        } else {
                            $meeting->append(['has_report', 'has_protocol']);
                        }
                    });
                    $institution->append('has_public_meetings');
                    $institution->append('meeting_periodicity_days');

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
            // Lazy load tenant institutions - only fetched when tenant tab is opened
            // Expects 'tenantIds' parameter in the reload request
            'tenantInstitutions' => Inertia::optional(function () use ($excludedTypeIds, $appendInstitutionAttributes, $userDutyInstitutionIds) {
                $tenantIds = request()->input('tenantIds', []);
                $institutions = DutyService::getInstitutionsForTenants($tenantIds, $this->authorizer);

                // Apply same filtering as user institutions
                if ($excludedTypeIds->isNotEmpty()) {
                    $institutions = $institutions->filter(function ($institution) use ($excludedTypeIds) {
                        return $institution->types->pluck('id')->intersect($excludedTypeIds)->isEmpty();
                    })->values();
                }

                // Append computed attributes (pass userDutyInstitutionIds for subscription status)
                $appendInstitutionAttributes($institutions, $userDutyInstitutionIds);

                return $institutions->values();
            }),
            // Lazy load representative activity stats - loaded together with tenantInstitutions
            // Returns login activity stats and categorized user lists for the activity dashboard cards
            'representativeActivity' => Inertia::optional(function () {
                $tenantIds = request()->input('tenantIds', []);

                return DutyService::getRepresentativeActivityForTenants($tenantIds);
            }),
            'availableTenants' => $availableTenants,
            // Note: recentMeetings is fetched via API endpoint: api.v1.admin.meetings.recent
        ]);
    }

    public function svetaine()
    {
        $this->handleAuthorization('viewAny', Page::class);

        $selectedTenant = request()->input('tenant_id');

        // Leave only tenants that are not 'pkp'
        $tenants = collect(GetTenantsForUpserts::execute('pages.update.padalinys', $this->authorizer))->filter(function ($tenant) {
            return $tenant['type'] !== 'pkp';
        })->values();

        // Check if selected tenant is in the list of tenants
        if ($selectedTenant) {
            $selectedTenant = $tenants->firstWhere('id', $selectedTenant);
        } else {
            // Check if there's tenant with type 'pagrindinis'
            $selectedTenant = $tenants->firstWhere('type', 'pagrindinis');
        }

        // If not, select first tenant
        if (! $selectedTenant) {
            $selectedTenant = $tenants->first();
        }

        if (! $selectedTenant) {
            $providedTenant = null;
        } else {
            $providedTenant = Tenant::query()->where('id', $selectedTenant['id'])->with('pages', 'news', 'quickLinks')->with(['calendar' => function ($query) {
                // get only future and pasts event 12 months ago
                $query->where('date', '>=', now()->subMonths(12))->orderBy('date', 'asc');
            }])->first();
        }

        return $this->inertiaResponse('Admin/Dashboard/ShowSvetaine', [
            'tenants' => $tenants,
            'providedTenant' => $providedTenant,
        ]);
    }

    public function reservations()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        $this->authorizer = $this->authorizer->forUser($user);

        /**
         * Resolve the tenants whose resources this user manages once.
         * ReservationResource::canBeApprovedBy() applies the same rule per pivot, which would
         * re-resolve the authorizer — and query the reservation's users — for every single row.
         * getTenants() is relative to the last checked permission, hence the explicit check().
         */
        $managesResources = $this->authorizer->check(config('permission.resource_managership_indicating_permission'));

        /**
         * The check result must gate getTenants(): with no permissable duties it falls back to
         * *all* of the user's duties, which would hand resource-management rights to someone who
         * merely holds a duty in the tenant. canBeApprovedBy() gates it the same way.
         */
        $managedTenantIds = $managesResources
            ? $this->authorizer->getTenants()->pluck('id')
            : collect();

        $eagerLoads = [
            'resources.tenant:id,shortname',
            'users:id,name,email,profile_photo_path',
        ];

        $myReservations = $user->reservations()->with($eagerLoads)->get();

        $administeredReservations = Reservation::query()
            ->whereHas('resources', fn ($query) => $query->whereIn('resources.tenant_id', $managedTenantIds))
            ->with($eagerLoads)
            ->get();

        return $this->inertiaResponse('Admin/Dashboard/ShowReservations', [
            'myReservations' => $this->serializeReservations($myReservations, $managedTenantIds, $user),
            'administeredReservations' => $this->serializeReservations($administeredReservations, $managedTenantIds, $user),
            // KPI counts are derived on the client, from whatever the table is currently showing:
            // the tiles filter the table, so their numbers have to agree with the rows.
            'managedTenants' => Tenant::query()
                ->whereIn('id', $managedTenantIds)
                ->orderBy('shortname')
                ->get(['id', 'shortname']),
        ]);
    }

    /**
     * Flatten reservations for the dashboard table.
     *
     * Each resource carries its pivot plus two permission flags that mirror the two branches of
     * ReservationResource::canBeApprovedBy(), so the table never offers an action the server
     * would reject. Pivot fields are listed explicitly: the pivot model declares
     * $with = ['comments', 'approvals'], which has no business in a list payload.
     *
     * @param  SupportCollection<int, Reservation>  $reservations
     * @param  SupportCollection<int, int>  $managedTenantIds
     * @return list<array<string, mixed>>
     */
    private function serializeReservations(SupportCollection $reservations, SupportCollection $managedTenantIds, User $user): array
    {
        return $reservations->map(function (Reservation $reservation) use ($managedTenantIds, $user) {
            $isParticipant = $reservation->users->contains('id', $user->id);

            return [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'description' => $reservation->description,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'created_at' => $reservation->created_at,
                'users' => $reservation->users
                    ->map(fn (User $manager) => $this->serializeReservationUser($manager))
                    ->values()
                    ->all(),
                'resources' => $reservation->resources
                    ->map(fn (Resource $resource) => $this->serializeReservationResource($resource, $managedTenantIds, $isParticipant))
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * Serialize one reserved resource, with the pivot the table acts on.
     *
     * @param  SupportCollection<int, int>  $managedTenantIds
     * @return array<string, mixed>
     */
    private function serializeReservationResource(Resource $resource, SupportCollection $managedTenantIds, bool $isParticipant): array
    {
        $pivot = $resource->pivot;
        $state = $pivot->state->getValue();

        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'tenant' => [
                'id' => $resource->tenant->id,
                'shortname' => $resource->tenant->shortname,
            ],
            'pivot' => [
                'id' => $pivot->id,
                'reservation_id' => $pivot->reservation_id,
                'resource_id' => $pivot->resource_id,
                'start_time' => $pivot->start_time,
                'end_time' => $pivot->end_time,
                'returned_at' => $pivot->returned_at,
                // Fallback for returned_at, which is only stamped on items returned
                // since it started being written.
                'updated_at' => $pivot->updated_at,
                'quantity' => $pivot->quantity,
                'state' => $state,
                'state_properties' => $pivot->state_properties,
                'approvable' => $managedTenantIds->contains($resource->tenant_id),
                'cancellable' => $isParticipant && in_array($state, ['created', 'reserved'], true),
            ],
        ];
    }

    /**
     * Serialize a single user attached to a reservation.
     *
     * @return array<string, mixed>
     */
    private function serializeReservationUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ];
    }

    public function userSettings()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        $user->load('roles:id,name',
            'current_duties:id,name,institution_id',
            'current_duties.roles:id,name', 'current_duties.roles.permissions:id,name',
            'current_duties.institution:id,tenant_id',
            'current_duties.institution.tenant:id,shortname')->makeVisible(['name_was_changed', 'show_pronouns']);

        return $this->inertiaResponse('Admin/ShowUserSettings', [
            'user' => $user->append('has_password')->toFullArray(),
            'notificationPreferences' => $user->notification_preferences,
            'notificationCategories' => NotificationCategory::toOptions(),
            'notificationChannels' => NotificationChannel::toOptions(),
            'availableDigestEmails' => $user->getAvailableDigestEmails(),
        ]);
    }

    public function updateUserSettings(Request $request)
    {
        $user = User::find(Auth::id());

        // Only self-service profile fields — never email/password, which have
        // their own dedicated flows (updatePassword).
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'profile_photo_path' => ['nullable', 'string'],
            'profile_photo_focal_point' => ['nullable', 'array'],
            'pronouns' => ['nullable', 'array'],
            'show_pronouns' => ['nullable', 'boolean'],
        ]);

        // The display name may only be changed once.
        if (array_key_exists('name', $validated) && $user->name !== $validated['name'] && ! $user->name_was_changed) {
            $user->name_was_changed = true;
        } else {
            unset($validated['name']);
        }

        $user->update($validated);

        return $this->redirectBackWithSuccess('Nustatymai išsaugoti.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = User::find(Auth::id());

        $user->password = bcrypt($request->password);
        $user->save();

        return redirect()->back()->with('success', 'Slaptažodis sėkmingai pakeistas.');
    }

    public function updateNotificationPreferences(Request $request)
    {
        $user = User::find(Auth::id());

        $validated = $request->validate([
            'channels' => 'nullable|array',
            'channels.*' => 'nullable|array',
            'channels.*.*' => 'boolean',
            'digest_frequency_hours' => 'nullable|integer|min:1|max:24',
            'digest_emails' => 'nullable|array',
            'digest_emails.*' => 'email',
            'muted_until' => 'nullable|date',
            'reminder_settings' => 'nullable|array',
            'reminder_settings.task_reminder_days' => 'nullable|array',
            'reminder_settings.task_reminder_days.*' => 'integer|min:1',
            'reminder_settings.meeting_reminder_hours' => 'nullable|array',
            'reminder_settings.meeting_reminder_hours.*' => 'integer|min:1',
            'reminder_settings.calendar_reminder_hours' => 'nullable|array',
            'reminder_settings.calendar_reminder_hours.*' => 'integer|min:1',
        ]);

        $preferences = $user->notification_preferences;

        if (isset($validated['channels'])) {
            $preferences['channels'] = $validated['channels'];
        }

        if (isset($validated['digest_frequency_hours'])) {
            $preferences['digest_frequency_hours'] = $validated['digest_frequency_hours'];
        }

        if (array_key_exists('digest_emails', $validated)) {
            // Validate against available emails (only store valid ones)
            $availableEmails = collect($user->getAvailableDigestEmails())->pluck('email')->toArray();
            $preferences['digest_emails'] = array_values(array_intersect($validated['digest_emails'] ?? [], $availableEmails));
        }

        if (array_key_exists('muted_until', $validated)) {
            $preferences['muted_until'] = $validated['muted_until'];
        }

        if (isset($validated['reminder_settings'])) {
            $preferences['reminder_settings'] = array_merge(
                $preferences['reminder_settings'] ?? [],
                $validated['reminder_settings']
            );
        }

        $user->notification_preferences = $preferences;
        $user->save();

        return $this->redirectBackWithSuccess('Pranešimų nustatymai išsaugoti.');
    }

    /**
     * Send a sample digest email to the current user's configured digest addresses.
     *
     * Sent with sendNow() rather than send(): NotificationDigest is a ShouldQueue
     * mailable, so send() would only enqueue it and report success even when the
     * mail server is rejecting everything — which defeats the point of a test.
     */
    public function sendTestNotificationEmail(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $emails = $user->getDigestEmails();

        // Build the sample from a real notification so the item shape cannot drift
        // away from what the digest template expects.
        $notification = new TestPushNotification;

        $sampleItems = [
            $notification->category()->value => [$notification->toDigestItem($user)],
        ];

        try {
            Mail::to($emails)->sendNow(new NotificationDigest($user, $sampleItems));
        } catch (TransportExceptionInterface $e) {
            return $this->jsonError(
                __('notifications.test_email_failed', ['error' => $e->getMessage()]),
                500
            );
        }

        return $this->jsonSuccess(
            message: __('notifications.test_email_sent', ['emails' => implode(', ', $emails)])
        );
    }

    public function userTasks(Request $request)
    {
        $user = User::find(Auth::id());

        $tasksQuery = $user->tasks()->with('taskable', 'users:id,name,email,profile_photo_path');

        // Get task statistics (before any filtering for accurate counts)
        $taskStats = [
            'total' => (clone $tasksQuery)->whereNull('completed_at')->count(),
            'completed' => (clone $tasksQuery)->whereNotNull('completed_at')->count(),
            'overdue' => (clone $tasksQuery)->whereNull('completed_at')->where('due_date', '<', now())->count(),
            'autoCompleting' => (clone $tasksQuery)->whereNull('completed_at')->whereNotNull('action_type')->where('action_type', '!=', 'manual')->count(),
        ];

        // Apply status filter
        $status = $request->input('status', 'incomplete');

        match ($status) {
            'completed' => $tasksQuery->whereNotNull('completed_at'),
            'incomplete' => $tasksQuery->whereNull('completed_at'),
            default => null, // 'all' - no filter
        };

        // Apply ordering based on status filter
        if ($status === 'completed') {
            // Completed tasks: most recently completed first
            $tasksQuery->latest('completed_at');
        } else {
            // Incomplete/all: overdue first, then by due date, then by creation date
            $tasksQuery
                // Put incomplete tasks first when showing all
                ->orderByRaw('completed_at IS NOT NULL')
                ->orderByRaw('CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 0 ELSE 1 END', [now()])
                ->orderBy('due_date')
                ->latest('created_at');
        }

        // Paginate tasks
        $perPage = $request->input('per_page', 20);
        $paginatedTasks = $tasksQuery->paginate($perPage)->withQueryString();

        // Transform tasks with computed properties
        $tasks = $paginatedTasks->through(fn ($task) => [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'due_date' => $task->due_date?->toISOString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'created_at' => $task->created_at?->toISOString(),
            'action_type' => $task->action_type?->value,
            'metadata' => $task->metadata,
            'progress' => $task->getProgress(),
            'is_overdue' => $task->isOverdue(),
            'can_be_manually_completed' => $task->canBeManuallyCompleted(),
            'icon' => $task->icon,
            'color' => $task->color,
            // Subject model - lightweight taskable info
            'taskable' => $task->taskable ? [
                'id' => $task->taskable->id,
                'name' => $task->taskable->title ?? $task->taskable->name ?? null,
                'type' => class_basename($task->taskable_type),
            ] : null,
            'taskable_type' => class_basename($task->taskable_type ?? ''),
            'taskable_id' => $task->taskable_id,
            'users' => $task->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'profile_photo_path' => $u->profile_photo_path,
            ]),
        ]);

        return $this->inertiaResponse('Admin/ShowTasks', [
            'tasks' => $tasks,
            'taskStats' => $taskStats,
            'status' => $status,
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

    public function sendFeedback(Request $request)
    {
        $request->validate([
            'feedback' => 'required|string',
            'anonymous' => 'boolean',
        ]);

        // just send simple email to it@vusa.lt with feedback, conditional user name and with in a queue
        Mail::to('it@vusa.lt')->queue(new FeedbackMail($request->input('feedback'), $request->input('anonymous') ? null : Auth::user()));

        return $this->redirectBackWithSuccess('Ačiū už atsiliepimą!');
    }

    // Removed atstovavimasSummary endpoint and related view as per simplification request.
}
