<?php

use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
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
                ->has('unreadNotificationsCount')
                ->has('hasNotifications')
                ->has('upcomingTasks')
                ->has('upcomingMeetings')
                ->has('institutionsNeedingAttention')
                ->has('upcomingCalendarEvents')
                ->has('latestNews')
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
                ->has('unreadNotificationsCount')
                ->has('hasNotifications')
                ->has('upcomingTasks')
                ->has('upcomingMeetings')
                ->has('institutionsNeedingAttention')
                ->has('upcomingCalendarEvents')
                ->has('latestNews')
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

    test('notification data is correctly structured', function (): void {
        asUser($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowAdminHome')
                ->has('unreadNotificationsCount')
                ->has('hasNotifications')
                ->where('unreadNotificationsCount', fn ($count) => is_numeric($count) && $count >= 0)
                ->where('hasNotifications', fn ($hasNotifications) => is_bool($hasNotifications))
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
                ->has('institutionsNeedingAttention')
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
                ->has('upcomingCalendarEvents')
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
                ->has('upcomingCalendarEvents', 0)
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
                ->has('upcomingCalendarEvents', 0)
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
                ->has('latestNews')
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
                ->has('latestNews', 0)
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
                ->has('latestNews', 0)
            );
    });

    test('news without image returns null image', function (): void {
        // Delete any existing news first
        News::query()->delete();

        // Create news without an image
        News::factory()->for($this->tenant)->create([
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
                ->has('latestNews', 1)
                ->where('latestNews.0.image', null)
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
                ->has('latestNews', 1)
                ->where('latestNews.0.image', 'https://example.com/news-image.jpg')
            );
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
                ->where('institutionsNeedingAttention', function ($institutions) use ($institution) {
                    $entry = collect($institutions)->firstWhere('id', $institution->id);

                    return $entry !== null
                        && $entry['status'] === 'overdue'
                        && $entry['effective_days_since_activity'] === 45
                        && $entry['requires_action'] === true;
                })
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
                ->where('institutionsNeedingAttention', fn ($institutions) => collect($institutions)->firstWhere('id', $institution->id) === null)
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
                ->where('institutionsNeedingAttention', fn ($institutions) => collect($institutions)->firstWhere('id', $institution->id) === null)
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
