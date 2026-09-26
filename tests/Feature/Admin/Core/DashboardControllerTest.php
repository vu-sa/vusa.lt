<?php

use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\ReservationDraft;
use App\Models\ReservationDraftItem;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use App\Settings\FormSettings;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create related test data
    $this->page = Page::factory()->for($this->tenant)->create();
    $this->news = News::factory()->for($this->tenant)->create();
    $this->quickLink = QuickLink::factory()->for($this->tenant)->create();
    $this->resource = Resource::factory()->for($this->tenant)->create();
});
describe('dashboard access', function (): void {
    test('any authenticated user can access dashboard', function (): void {
        // Dashboard is accessible to any authenticated user
        asUser($this->user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('taskStats')
                ->has('upcomingTasks')
                ->has('upcomingMeetings')
                ->missing('institutionsNeedingAttention')
                ->missing('upcomingCalendarEvents')
                ->missing('latestNews')
                ->missing('followedInstitutions')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('institutionsNeedingAttention')
                    ->has('upcomingCalendarEvents')
                    ->has('latestNews')
                    ->has('recentlyEdited')
                    ->has('coordinator')
                    ->has('followedInstitutions')
                )
            );
    });

    test('admin can access dashboard', function (): void {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('taskStats')
                ->has('taskStats.total')
                ->has('taskStats.overdue')
                ->has('taskStats.dueSoon')
                ->has('upcomingTasks')
                ->has('upcomingMeetings')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('institutionsNeedingAttention')
                    ->has('upcomingCalendarEvents')
                    ->has('latestNews')
                )
            );
    });

    test('unauthenticated user cannot access dashboard', function (): void {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});

describe('dashboard data structure', function (): void {
    test('task statistics are correctly structured', function (): void {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('taskStats')
                ->where('taskStats', fn ($taskStats) => isset($taskStats['total'])
                    && isset($taskStats['overdue'])
                    && isset($taskStats['dueSoon']))
            );
    });

    test('upcoming data is included', function (): void {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('upcomingTasks')
                ->has('upcomingMeetings')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('institutionsNeedingAttention'))
            );
    });
});

describe('followed institutions', function (): void {
    test('upcoming meetings include followed institutions, marked as followed', function (): void {
        $followed = Institution::factory()->for($this->tenant)->create();
        $ownInstitutionId = $this->user->current_duties->first()->institution_id;

        Meeting::factory()->hasAttached($followed)->create(['start_time' => now()->addDays(2)]);
        Meeting::factory()->hasAttached(Institution::find($ownInstitutionId))->create(['start_time' => now()->addDay()]);
        Meeting::factory()->hasAttached(Institution::factory()->for($this->tenant))->create(['start_time' => now()->addDays(3)]);
        $this->user->followedInstitutions()->attach($followed);

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingMeetingsTotal', 2)
                ->where('upcomingMeetings.0.is_followed', false)
                ->where('upcomingMeetings.1.is_followed', true)
                ->where('upcomingMeetings.1.institution_id', $followed->id)
            );
    });

    test('the followed list is capped but counts every follow', function (): void {
        $institutions = Institution::factory()->for($this->tenant)->count(7)->create();
        $this->user->followedInstitutions()->attach($institutions);

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('followedInstitutions.items', 5)
                    ->where('followedInstitutions.total', 7)
                )
            );
    });
});

describe('dashboard performance', function (): void {
    test('dashboard loads without database errors', function (): void {
        // Test that the dashboard doesn't cause N+1 queries or database errors
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200);
    });

    test('dashboard task statistics work with no tasks', function (): void {
        // Test when user has no tasks
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->where('taskStats.total', 0)
                ->where('taskStats.overdue', 0)
                ->where('taskStats.dueSoon', 0)
            );
    });
});

describe('dashboard tasks with due dates', function (): void {
    test('shows later due dates before older and overdue tasks', function (): void {
        foreach ([
            'Overdue' => now()->subDays(2),
            'Soon' => now()->addDay(),
            'Later' => now()->addDays(12),
        ] as $name => $dueDate) {
            $task = Task::factory()->create([
                'name' => $name,
                'due_date' => $dueDate,
                'completed_at' => null,
            ]);
            $task->users()->attach($this->admin->id);
        }

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingTasks.0.name', 'Later')
                ->where('upcomingTasks.1.name', 'Soon')
                ->where('upcomingTasks.2.name', 'Overdue')
            );
    });

    test('tasks with due dates are properly formatted', function (): void {
        // Create a task with a due date for the admin user
        $task = Task::factory()->create([
            'name' => 'Test Task',
            'due_date' => now()->addDays(3),
            'completed_at' => null,
        ]);
        $task->users()->attach($this->admin->id);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('upcomingTasks')
                ->where('upcomingTasks.0.name', 'Test Task')
                ->where('upcomingTasks.0.is_overdue', false)
                ->has('upcomingTasks.0.due_date')
            );
    });

    test('a task names the subject it is about so the queue can link to it', function (): void {
        $meeting = Meeting::factory()->create(['title' => 'Senato posėdis']);
        $task = Task::factory()->create([
            'name' => 'Užpildyti darbotvarkę',
            'due_date' => now()->addDays(2),
            'completed_at' => null,
            'taskable_type' => 'meeting',
            'taskable_id' => $meeting->id,
        ]);
        $task->users()->attach($this->admin->id);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingTasks.0.taskable.name', 'Senato posėdis')
                ->where('upcomingTasks.0.taskable.id', (string) $meeting->id)
            );
    });

    test('overdue tasks are correctly identified', function (): void {
        // Create an overdue task
        $task = Task::factory()->create([
            'name' => 'Overdue Task',
            'due_date' => now()->subDays(2),
            'completed_at' => null,
        ]);
        $task->users()->attach($this->admin->id);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->where('taskStats.overdue', 1)
            );
    });

    test('completed tasks are not included in statistics', function (): void {
        // Create a completed task
        $task = Task::factory()->create([
            'name' => 'Completed Task',
            'due_date' => now()->addDays(1),
            'completed_at' => now(),
        ]);
        $task->users()->attach($this->admin->id);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->where('taskStats.total', 0)
            );
    });

    test('tasks due within 7 days are counted as dueSoon', function (): void {
        // Create a task due in 5 days
        $task = Task::factory()->create([
            'name' => 'Due Soon Task',
            'due_date' => now()->addDays(5),
            'completed_at' => null,
        ]);
        $task->users()->attach($this->admin->id);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->where('taskStats.dueSoon', 1)
            );
    });
});

describe('dashboard calendar and news', function (): void {
    test('upcoming calendar events are included', function (): void {
        // Create upcoming calendar events
        Calendar::factory()->for($this->tenant)->create([
            'title' => ['lt' => 'Test Event LT', 'en' => 'Test Event EN'],
            'date' => now()->addDays(5),
            'is_draft' => false,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('upcomingCalendarEvents'))
            );
    });

    test('draft calendar events are not included', function (): void {
        // Clear existing calendar events
        Calendar::query()->delete();

        // Create a draft calendar event
        Calendar::factory()->for($this->tenant)->create([
            'title' => ['lt' => 'Draft Event', 'en' => 'Draft Event'],
            'date' => now()->addDays(5),
            'is_draft' => true,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('upcomingCalendarEvents', 0))
            );
    });

    test('past calendar events are not included', function (): void {
        // Clear existing calendar events
        Calendar::query()->delete();

        // Create a past calendar event
        Calendar::factory()->for($this->tenant)->create([
            'title' => ['lt' => 'Past Event', 'en' => 'Past Event'],
            'date' => now()->subDays(5),
            'is_draft' => false,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('upcomingCalendarEvents', 0))
            );
    });

    test('latest news items are included', function (): void {
        // Create published news
        News::factory()->for($this->tenant)->create([
            'title' => 'Latest News',
            'lang' => 'lt',
            'publish_time' => now()->subHours(1),
            'draft' => null,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('latestNews'))
            );
    });

    test('draft news items are not included', function (): void {
        // Delete any existing news first
        News::query()->delete();

        // Create draft news
        News::factory()->for($this->tenant)->create([
            'title' => 'Draft News',
            'lang' => 'lt',
            'publish_time' => now()->subHours(1),
            'draft' => 1,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('latestNews', 0))
            );
    });

    test('future news items are not included', function (): void {
        // Delete any existing news first
        News::query()->delete();

        // Create future news (scheduled)
        News::factory()->for($this->tenant)->create([
            'title' => 'Future News',
            'lang' => 'lt',
            'publish_time' => now()->addDays(1),
            'draft' => null,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->has('latestNews', 0))
            );
    });

    test('news without image returns null image', function (): void {
        // Delete any existing news first
        News::query()->delete();

        // Create news without an image
        $news = News::factory()->for($this->tenant)->create([
            'title' => 'News Without Image',
            'lang' => 'lt',
            'image' => null,
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('latestNews', 1)
                    ->where('latestNews.0.image', null)
                    ->where('latestNews.0.public_url', $news->publicUrl())
                )
            );
    });

    test('news with external image returns actual image URL', function (): void {
        // Delete any existing news first
        News::query()->delete();

        // Create news with an external image URL
        News::factory()->for($this->tenant)->create([
            'title' => 'News With External Image',
            'lang' => 'lt',
            'image' => 'https://example.com/news-image.jpg',
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);

        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('latestNews', 1)
                    ->where('latestNews.0.image', 'https://example.com/news-image.jpg')
                )
            );
    });
});

describe('hero image', function (): void {
    test('uses the primary institution image of a tenant from the user\'s current duties', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create([
            'image_url' => 'https://example.com/institution.jpg',
            'image_focal_point' => '40% 30%',
        ]);
        $this->tenant->update(['primary_institution_id' => $institution->id]);

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('heroImage.url', 'https://example.com/institution.jpg')
                ->where('heroImage.focalPoint', '40% 30%')
                ->missing('heroNews')
            );
    });

    test('uses the community fallback when the duty tenant has no primary institution image', function (): void {
        $this->tenant->update(['primary_institution_id' => null]);

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('heroImage', null));
    });
});

describe('institutions needing attention', function (): void {
    /**
     * Create an institution with a 30-day periodicity, a representative and one past meeting.
     */
    function institutionForUserWithMeeting(string $lastMeetingDate): array
    {
        $tenant = Tenant::factory()->create(['type' => 'padalinys']);

        $institution = Institution::factory()->for($tenant)->create([
            'meeting_periodicity_days' => 30,
            'alias' => 'attention-test-'.uniqid(),
        ]);

        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        $user = User::factory()->create();
        $user->duties()->attach($duty, [
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => $lastMeetingDate]);

        return [$user, $institution];
    }

    test('flags an institution whose meeting gap exceeds its periodicity', function (): void {
        $this->travelTo('2025-11-15');

        [$user, $institution] = institutionForUserWithMeeting('2025-10-01 10:00:00');

        asUser($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->where('institutionsNeedingAttention', function ($institutions) use ($institution) {
                        $entry = collect($institutions)->firstWhere('id', $institution->id);

                        return $entry !== null
                            && $entry['status'] === 'overdue'
                            && $entry['effective_days_since_activity'] === 45
                            && $entry['requires_action'] === true;
                    })
                )
            );
    });

    test('does not flag an institution whose gap is made up of vacation days', function (): void {
        // June 20 -> September 1: 73 calendar days, but only 11 outside summer vacation.
        $this->travelTo('2025-09-01');

        [$user, $institution] = institutionForUserWithMeeting('2025-06-20 10:00:00');

        asUser($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->where('institutionsNeedingAttention', fn ($institutions) => collect($institutions)->firstWhere('id', $institution->id) === null)
                )
            );
    });

    test('counts a completed check-in as recent activity so the institution is not flagged', function (): void {
        // Same setup as the overdue test: meeting 45 days ago, 30-day periodicity.
        $this->travelTo('2025-11-15');

        [$user, $institution] = institutionForUserWithMeeting('2025-10-01 10:00:00');

        // A completed check-in ending 5 days ago should reset the activity clock,
        // matching what the ShowAtstovavimas page computes via DutyService.
        InstitutionCheckIn::factory()->create([
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'tenant_id' => $institution->tenant_id,
            'start_date' => '2025-11-05',
            'end_date' => '2025-11-10',
        ]);

        asUser($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->where('institutionsNeedingAttention', fn ($institutions) => collect($institutions)->firstWhere('id', $institution->id) === null)
                )
            );
    });
});

describe('Pradžia secondary panels', function (): void {
    test('the queue is served first and the secondary group is held back', function (): void {
        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingTasks')
                ->has('upcomingMeetings')
                ->missing('recentlyEdited')
                ->missing('coordinator')
            );
    });

    test('recently edited lists only records the user changed and can still view, newest first', function (): void {
        $institution = $this->user->duties()->first()->institution;
        $mine = Meeting::factory()->hasAttached($institution)->create(['title' => 'Mano posėdis']);
        $older = Meeting::factory()->hasAttached($institution)->create(['title' => 'Senesnis posėdis']);
        $notMine = Meeting::factory()->hasAttached($institution)->create(['title' => 'Svetimas posėdis']);
        $noAccess = Meeting::factory()->create(['title' => 'Nematomas posėdis']);

        activity()->performedOn($older)->causedBy($this->user)->log('updated');
        activity()->performedOn($noAccess)->causedBy($this->user)->log('updated');
        activity()->performedOn($notMine)->causedBy($this->admin)->log('updated');
        $this->travel(1)->minute();
        activity()->performedOn($mine)->causedBy($this->user)->log('updated');

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->where('recentlyEdited', fn ($records) => collect($records)->pluck('title')->all() === ['Mano posėdis', 'Senesnis posėdis'])
                    ->where('recentlyEdited.0.href', route('meetings.show', $mine))
                )
            );
    });

    test('recently edited collapses many edits of one record into one row', function (): void {
        $institution = $this->user->duties()->first()->institution;
        $meeting = Meeting::factory()->hasAttached($institution)->create(['title' => 'Vienas posėdis']);

        foreach (range(1, 4) as $ignored) {
            activity()->performedOn($meeting)->causedBy($this->user)->log('updated');
        }

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('recentlyEdited', 1)
                )
            );
    });

    test('the coordinator is the institution manager of the user\'s tenant, never the user', function (): void {
        $role = Role::factory()->create(['name' => 'Institution Manager']);
        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = $role->id;
        $settings->save();

        $manager = makeUser($this->tenant);
        $duty = $manager->duties()->first();
        $duty->pivot->end_date = null;
        $duty->pivot->save();
        $duty->assignRole($role->name);

        $rep = makeUser($this->tenant);
        $rep->duties()->first()->pivot->update(['end_date' => null]);

        asUser($rep)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->where('coordinator.name', $manager->name)
                )
            );

        // A manager is not their own coordinator.
        asUser($manager)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->where('coordinator', null))
            );
    });

    test('the coordinator is absent when no manager role is configured', function (): void {
        $settings = app(AtstovavimasSettings::class);
        $settings->institution_manager_role_id = null;
        $settings->save();

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page->where('coordinator', null))
            );
    });

    test('site content is only assembled for users who can see it', function (): void {
        News::factory()->for($this->tenant)->create([
            'lang' => 'lt',
            'publish_time' => now()->subHour(),
            'draft' => false,
        ]);

        asUser($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('secondary', fn (Assert $page) => $page
                    ->has('latestNews', 0)
                    ->has('upcomingCalendarEvents', 0)
                )
            );
    });
});

describe('Sparčioji prieiga registration forms', function (): void {
    beforeEach(function (): void {
        $this->memberForm = Form::factory()->for($this->tenant)->create();
        $this->recipientRole = Role::factory()->create(['name' => 'Member Registration Recipient']);

        $formSettings = app(FormSettings::class);
        $formSettings->member_registration_form_id = $this->memberForm->id;
        $formSettings->member_registration_notification_recipient_role_id = $this->recipientRole->id;
        $formSettings->save();
    });

    test('a registration recipient gets a link to the member registration form', function (): void {
        $recipient = makeUser($this->tenant);
        $recipient->assignRole($this->recipientRole->name);

        asUser($recipient)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('registrationForms', [['key' => 'member', 'href' => route('forms.show', $this->memberForm)]])
        );
    });

    test('a user who cannot view the form gets no link', function (): void {
        asUser($this->user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('registrationForms', [])
        );
    });
});

describe('institution graph', function (): void {
    test('authenticated user can access institution graph', function (): void {
        asUser($this->admin)
            ->get(route('institutionGraph'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowInstitutionGraph')
                ->has('institutions')
                ->has('institutionRelationships')
            );
    });

    test('institution graph includes user counts', function (): void {
        asUser($this->admin)
            ->get(route('institutionGraph'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowInstitutionGraph')
                ->where('institutions', fn ($institutions) => collect($institutions)->every(fn ($institution) => isset($institution['users_count']) && is_numeric($institution['users_count'])))
            );
    });
});

describe('unfinished reservation', function (): void {
    test('home points back to a started reservation', function (): void {
        asUser($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('reservationDraft', null));

        $draft = ReservationDraft::factory()->for($this->user)->withPeriod()->create();
        ReservationDraftItem::factory()->for($draft, 'draft')->for($this->resource)->create();

        asUser($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservationDraft.count', 1)
                ->where('reservationDraft.name', null)
                ->where('reservationDraft.start_time', $draft->start_time->getTimestampMs())
            );
    });

    test('a named draft with nothing picked yet still shows', function (): void {
        ReservationDraft::factory()->for($this->user)->create(['name' => 'Stovykla']);

        asUser($this->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reservationDraft.name', 'Stovykla')
                ->where('reservationDraft.count', 0)
            );
    });
});
