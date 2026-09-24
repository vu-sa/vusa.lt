<?php

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

describe('user tasks', function (): void {
    test('both doors render the same task collection, each in its own scope', function (): void {
        asUser($this->admin)
            ->get(route('userTasks'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tasks/IndexTask')
                ->where('scope', 'mine')
                ->has('data')
                ->has('taskCounts')
            );

        asUser(makeAdminUser($this->tenant))
            ->get(route('tasks.summary'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tasks/IndexTask')
                ->where('scope', 'tenant')
            );
    });

    test('lists only the tasks assigned to the user', function (): void {
        $mine = Task::factory()->create();
        $mine->users()->attach($this->user);
        $theirs = Task::factory()->create();
        $theirs->users()->attach($this->admin);

        asUser($this->user)
            ->get(route('userTasks'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('data', 1)
                ->where('data.0.id', $mine->id)
            );
    });

    test('offers the way to all tasks only to a user who may read them', function (): void {
        asUser($this->user)
            ->get(route('userTasks'))
            ->assertInertia(fn (Assert $page) => $page->where('canViewAllTasks', false));

        asUser(makeAdminUser($this->tenant))
            ->get(route('userTasks'))
            ->assertInertia(fn (Assert $page) => $page->where('canViewAllTasks', true));
    });

    test('a plain member is refused the tenant task list', function (): void {
        asUser($this->user)->get(route('tasks.summary'))->assertForbidden();
    });

    test('an ?item= outside the scope does not resolve', function (): void {
        $theirs = Task::factory()->create();
        $theirs->users()->attach($this->admin);

        asUser($this->user)
            ->get(route('userTasks', ['item' => $theirs->id]))
            ->assertInertia(fn (Assert $page) => $page->where('linkedTask', null));
    });

    test('unauthenticated user cannot access user tasks', function (): void {
        $this->get(route('userTasks'))
            ->assertRedirect(route('login'));
    });
});

describe('task collection API', function (): void {
    test('the mine scope never returns another user\'s tasks', function (): void {
        $mine = Task::factory()->create();
        $mine->users()->attach($this->user);
        Task::factory()->create()->users()->attach($this->admin);

        asUser($this->user)
            ->getJson(route('api.v1.admin.tasks.index', ['scope' => 'mine']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $mine->id);
    });

    test('the tenant scope is forbidden without tasks.read.padalinys', function (): void {
        asUser($this->user)
            ->getJson(route('api.v1.admin.tasks.index', ['scope' => 'tenant']))
            ->assertForbidden();
    });

    test('a padalinys the user cannot read narrows the tenant scope to nothing', function (): void {
        $manager = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $task = Task::factory()->create(['taskable_type' => 'institution', 'taskable_id' => 'gone']);
        $task->users()->attach($manager);

        asUser($manager)
            ->getJson(route('api.v1.admin.tasks.index', ['scope' => 'tenant']))
            ->assertJsonPath('data.total', 1);

        asUser($manager)
            ->getJson(route('api.v1.admin.tasks.index', ['scope' => 'tenant', 'tenant' => [$otherTenant->id]]))
            ->assertJsonPath('data.total', 0);
    });

    test('filters to overdue tasks', function (): void {
        $late = Task::factory()->create(['due_date' => now()->subDay()]);
        $late->users()->attach($this->user);
        Task::factory()->create(['due_date' => now()->addWeek()])->users()->attach($this->user);

        asUser($this->user)
            ->getJson(route('api.v1.admin.tasks.index', ['overdue' => '1']))
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.id', $late->id);
    });
});
