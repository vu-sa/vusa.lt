<?php

use App\Enums\GoalStatus;
use App\Enums\MeetingType;
use App\Models\Duty;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Role;
use App\Models\Step;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    [$this->tenant, $this->otherTenant] = Tenant::query()->inRandomOrder()->take(2)->get();

    $this->tenant->update(['goals_enabled' => true]);

    Role::findOrCreate('Tikslų bandytojas', 'web')->givePermissionTo([
        'goals.create.padalinys',
        'goals.update.padalinys',
        'goals.delete.padalinys',
    ]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->editor = makeTenantUserWithRole('Tikslų bandytojas', $this->tenant);
    $this->goal = Goal::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('experiment switch', function (): void {
    test('tenants are disabled by default and tenant forms cannot enroll them', function (): void {
        expect($this->otherTenant->goals_enabled)->toBeFalse();

        asUser(makeAdminUser($this->tenant))->patch(route('tenants.update', $this->otherTenant), [
            'fullname' => $this->otherTenant->fullname,
            'shortname' => $this->otherTenant->shortname,
            'type' => $this->otherTenant->type->value,
            'goals_enabled' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($this->otherTenant->fresh()->goals_enabled)->toBeFalse();
    });

    test('a current membership in any enabled tenant admits a user', function (): void {
        $user = makeUser($this->otherTenant);
        $duty = $this->editor->duties()->first();
        $user->duties()->attach($duty, ['start_date' => today()->subDay()]);

        asUser($user)->get(route('goals.index'))->assertOk();
    });

    test('creation permissions in a disabled tenant do not expose the create action', function (): void {
        $user = makeTenantUserWithRole('Tikslų bandytojas', $this->otherTenant);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $user->duties()->attach($duty, ['start_date' => today()->subDay()]);

        asUser($user)->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canCreate', false));
        asUser($user)->get(route('goals.create'))->assertForbidden();
    });

    test('pilot access follows the dates of the membership', function (int $startOffset, ?int $endOffset, int $status): void {
        $pivot = $this->editor->duties()->first()->pivot;
        $pivot->start_date = today()->addDays($startOffset);
        $pivot->end_date = $endOffset === null ? null : today()->addDays($endOffset);
        $pivot->save();

        asUser($this->editor)->get(route('goals.index'))->assertStatus($status);
    })->with([
        'ended yesterday' => [-2, -1, 404],
        'starts tomorrow' => [1, null, 404],
        'ends today' => [-2, 0, 200],
    ]);

    test('disabled tenant records are inaccessible to pilot members and super admins', function (): void {
        $goal = Goal::factory()->create(['tenant_id' => $this->otherTenant->id]);

        foreach ([$this->editor, makeAdminUser($this->tenant)] as $user) {
            expect($user->can('view', $goal))->toBeFalse()
                ->and($user->can('update', $goal))->toBeFalse()
                ->and($user->can('delete', $goal))->toBeFalse();

            asUser($user)->get(route('goals.show', $goal))->assertNotFound();
            asUser($user)->get(route('goals.edit', $goal))->assertNotFound();
            asUser($user)->patch(route('goals.update', $goal), [])->assertNotFound();
            asUser($user)->delete(route('goals.destroy', $goal))->assertNotFound();
            asUser($user)->post(route('goals.steps.store', $goal), [])->assertNotFound();
        }

        expect($goal->fresh())->not->toBeNull();
    });

    test('a super admin without enabled memberships works only in enabled tenants', function (): void {
        $admin = makeAdminUser($this->otherTenant);

        asUser($admin)->get(route('goals.index'))->assertOk();
        asUser($admin)->get(route('goals.create'))
            ->assertInertia(fn (Assert $page) => $page->has('tenants', 1)->where('tenants.0.id', $this->tenant->id));
        asUser($admin)->post(route('goals.store'), [
            'title' => ['lt' => 'Tikslas', 'en' => 'Goal'],
            'tenant_id' => $this->otherTenant->id,
            'status' => GoalStatus::Planned->value,
        ])->assertSessionHasErrors('tenant_id');
        asUser($admin)->patch(route('goals.update', $this->goal), [
            'title' => ['lt' => 'Tikslas', 'en' => 'Goal'],
            'tenant_id' => $this->otherTenant->id,
            'status' => GoalStatus::Planned->value,
        ])->assertSessionHasErrors('tenant_id');

        $this->tenant->update(['goals_enabled' => false]);
        asUser($admin)->get(route('goals.index'))->assertNotFound();
        expect($admin->can('viewAny', Goal::class))->toBeFalse()
            ->and($admin->can('create', Goal::class))->toBeFalse();
    });

    test('the list hides disabled tenants and includes other enabled tenants', function (): void {
        $goal = Goal::factory()->create(['tenant_id' => $this->otherTenant->id]);

        asUser($this->editor)->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page->has('goals', 1)->where('goals.0.id', $this->goal->id));

        $this->otherTenant->update(['goals_enabled' => true]);

        asUser($this->editor)->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page->has('goals', 2));
        asUser($this->editor)->get(route('goals.show', $goal))->assertOk();
    });

    test('navigation follows database enrollment changes despite a warm cache', function (): void {
        $admin = makeAdminUser($this->otherTenant);
        $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);

        foreach ([true, false, true] as $enabled) {
            Tenant::query()->whereKey($this->tenant->id)->update(['goals_enabled' => $enabled]);

            asUser($admin)->get(route('problems.show', $problem))
                ->assertInertia(fn (Assert $page) => $page->where('adminNavigation.workspaces', function ($workspaces) use ($enabled): bool {
                    return collect($workspaces)->flatMap(fn ($workspace) => $workspace['sections'])->pluck('key')->contains('tikslai') === $enabled
                        && collect($workspaces)->flatMap(fn ($workspace) => $workspace['createActions'])->pluck('key')->contains('new_goal') === $enabled;
                }));
        }
    });

    test('every goal route is a 404 while the experiment is off', function (): void {
        $this->tenant->update(['goals_enabled' => false]);

        asUser($this->editor)->get(route('goals.index'))->assertNotFound();
        asUser($this->editor)->get(route('goals.show', $this->goal))->assertNotFound();
        asUser($this->editor)->post(route('goals.steps.store', $this->goal), [])->assertNotFound();
    });

    test('a member outside the pilot padaliniai gets a 404, one inside gets the page', function (): void {
        $outsider = makeTenantUserWithRole('Tikslų bandytojas', $this->otherTenant);

        asUser($outsider)->get(route('goals.index'))->assertNotFound();
        asUser($this->editor)->get(route('goals.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Goals/IndexGoal')->has('goals', 1));
    });

    test('the problem page offers goal links only inside the pilot', function (): void {
        $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->editor)->get(route('problems.show', $problem))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goalsExperiment', true)
                ->missing('goalLinks')
                ->loadDeferredProps(fn (Assert $reload) => $reload->has('goalLinks.goals')->has('goalLinks.steps')));

        $this->tenant->update(['goals_enabled' => false]);

        asUser($this->editor)->get(route('problems.show', $problem))
            ->assertInertia(fn (Assert $page) => $page->missing('goalsExperiment')->missing('goalLinks'));
    });

    test('an enabled membership does not reveal a disabled problem tenant panel', function (): void {
        $problem = Problem::factory()->create(['tenant_id' => $this->otherTenant->id]);
        $this->goal->problems()->attach($problem);

        asUser($this->editor)->get(route('problems.show', $problem))
            ->assertInertia(fn (Assert $page) => $page->missing('goalsExperiment')->missing('goalLinks'));
        asUser(makeAdminUser($this->tenant))->post(route('problems.steps.store', $problem), [])->assertNotFound();
    });
});

describe('goals', function (): void {
    test('the record shows its steps, linked problems and public page', function (): void {
        $problem = Problem::factory()->create(['tenant_id' => $this->otherTenant->id]);
        $this->goal->problems()->attach($problem);
        $this->goal->update(['is_public' => true]);
        Step::factory()->create(['goal_id' => $this->goal->id, 'problem_id' => $problem->id]);

        asUser($this->editor)->get(route('goals.show', $this->goal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Goals/ShowGoal')
                ->where('steps.0.problem.id', $problem->id)
                ->where('problems.0.id', $problem->id)
                ->where('goal.public_url', fn (string $url) => str_contains($url, '/lt/tikslai/'.$this->goal->id))
                ->where('can.update', true)
                ->missing('problemOptions'));

        asUser($this->editor)->get(route('goals.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Goals/CreateGoal')->where('tenants.0.id', $this->tenant->id)->has('statuses', 5));
    });

    test('an editor creates a goal in their padalinys', function (): void {
        asUser($this->editor)->post(route('goals.store'), [
            'title' => ['lt' => 'Atnaujinti bendrabučius', 'en' => ''],
            'tenant_id' => $this->tenant->id,
            'status' => GoalStatus::Planned->value,
            'is_public' => true,
        ])->assertRedirect();

        $goal = Goal::query()->whereKeyNot($this->goal->id)->sole();

        expect($goal->getTranslation('title', 'lt'))->toBe('Atnaujinti bendrabučius')
            ->and($goal->tenant_id)->toBe($this->tenant->id)
            ->and($goal->is_public)->toBeTrue()
            ->and($goal->created_by)->toBe($this->editor->id);
    });

    test('a goal cannot be created in another padalinys', function (): void {
        asUser($this->editor)->post(route('goals.store'), [
            'title' => ['lt' => 'Svetimas tikslas'],
            'tenant_id' => $this->otherTenant->id,
            'status' => GoalStatus::Planned->value,
        ])->assertSessionHasErrors('tenant_id');
    });

    test('another padalinys\' duty cannot be made responsible', function (): void {
        $foreignDuty = Duty::factory()->for(Institution::factory()->state(['tenant_id' => $this->otherTenant->id]))->create();

        asUser($this->editor)->patch(route('goals.update', $this->goal), [
            'title' => ['lt' => 'Tikslas'],
            'tenant_id' => $this->tenant->id,
            'status' => GoalStatus::InProgress->value,
            'responsible_duty_id' => $foreignDuty->id,
        ])->assertSessionHasErrors('responsible_duty_id');
    });

    test('an editor of one padalinys cannot change another padalinys\' goal', function (): void {
        $this->otherTenant->update(['goals_enabled' => true]);
        $foreignGoal = Goal::factory()->create(['tenant_id' => $this->otherTenant->id]);

        asUser($this->editor)->patch(route('goals.update', $foreignGoal), [
            'title' => ['lt' => 'Perimta'],
            'tenant_id' => $this->otherTenant->id,
            'status' => GoalStatus::Achieved->value,
        ])->assertForbidden();

        asUser($this->editor)->delete(route('goals.destroy', $foreignGoal))->assertForbidden();
    });
});

describe('steps and problems', function (): void {
    test('disabled linked goals and steps cannot be reached through an enabled problem', function (): void {
        $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);
        $goal = Goal::factory()->create(['tenant_id' => $this->otherTenant->id]);
        $goal->problems()->attach($problem);
        $step = Step::factory()->create(['goal_id' => $goal->id, 'problem_id' => $problem->id]);
        $admin = makeAdminUser($this->tenant);

        asUser($admin)->get(route('problems.show', $problem))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('goalLinks.goals', 0)->has('goalLinks.steps', 0)));
        asUser($admin)->post(route('problems.steps.store', $problem), [
            'title' => ['lt' => 'Veiksmas', 'en' => 'Step'],
            'happened_on' => today()->toDateString(),
            'goal_id' => $goal->id,
        ])->assertSessionHasErrors('goal_id');
        asUser($admin)->patch(route('problems.steps.update', ['problem' => $problem, 'step' => $step]), [])->assertNotFound();
        asUser($admin)->delete(route('problems.steps.destroy', ['problem' => $problem, 'step' => $step]))->assertNotFound();

        $this->otherTenant->update(['goals_enabled' => true]);
        asUser($admin)->get(route('problems.show', $problem))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('goalLinks.goals.0.id', $goal->id)->where('goalLinks.steps.0.id', $step->id)));
    });

    test('a step logged on a goal can also count towards a linked problem', function (): void {
        $problem = Problem::factory()->create(['tenant_id' => $this->otherTenant->id]);

        asUser($this->editor)->post(route('goals.problems.link', $this->goal), ['problem_id' => $problem->id])->assertRedirect();

        asUser($this->editor)->post(route('goals.steps.store', $this->goal), [
            'title' => ['lt' => 'Susitikimas su prorektoriumi'],
            'happened_on' => '2026-09-01',
            'problem_id' => $problem->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $step = $this->goal->steps()->sole();

        expect($step->problem_id)->toBe($problem->id)
            ->and($problem->steps()->pluck('id')->all())->toBe([$step->id]);
    });

    test('a step cannot point at a problem the goal is not linked to', function (): void {
        $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->editor)->post(route('goals.steps.store', $this->goal), [
            'title' => ['lt' => 'Veiksmas'],
            'happened_on' => '2026-09-01',
            'problem_id' => $problem->id,
        ])->assertSessionHasErrors('problem_id');
    });

    test('a step authorizes against its goal', function (): void {
        $member = makeUser($this->tenant);

        asUser($member)->post(route('goals.steps.store', $this->goal), [
            'title' => ['lt' => 'Veiksmas'],
            'happened_on' => '2026-09-01',
        ])->assertForbidden();
    });

    test('a step of another goal cannot be reached through this goal', function (): void {
        $otherGoal = Goal::factory()->create(['tenant_id' => $this->tenant->id]);
        $step = Step::factory()->create(['goal_id' => $otherGoal->id]);

        asUser($this->editor)->delete(route('goals.steps.destroy', ['goal' => $this->goal, 'step' => $step]))->assertNotFound();

        expect($step->fresh())->not->toBeNull();
    });
});

describe('step details', function (): void {
    test('a new step is done by its recorder unless others are named', function (): void {
        $colleague = makeUser($this->tenant);

        asUser($this->editor)->post(route('goals.steps.store', $this->goal), [
            'title' => ['lt' => 'Pats parašiau raštą'],
            'happened_on' => '2026-09-01',
        ])->assertSessionHasNoErrors();

        asUser($this->editor)->post(route('goals.steps.store', $this->goal), [
            'title' => ['lt' => 'Kolega susitiko su dekanu'],
            'happened_on' => '2026-09-02',
            'performers' => [$colleague->id],
            'url' => 'https://example.com/protokolas',
        ])->assertSessionHasNoErrors();

        $steps = $this->goal->steps()->with('performers')->get()->keyBy(fn (Step $step) => $step->getTranslation('title', 'lt'));

        expect($steps['Pats parašiau raštą']->performers->modelKeys())->toBe([$this->editor->id])
            ->and($steps['Kolega susitiko su dekanu']->performers->modelKeys())->toBe([$colleague->id])
            ->and($steps['Kolega susitiko su dekanu']->created_by)->toBe($this->editor->id)
            ->and($steps['Kolega susitiko su dekanu']->url)->toBe('https://example.com/protokolas');

        asUser($this->editor)->get(route('goals.show', $this->goal))
            ->assertInertia(fn (Assert $page) => $page
                ->where('steps.0.recorder.id', $this->editor->id)
                ->where('steps.0.performers.0.id', $colleague->id));
    });

    test('a step cannot point at an agenda item its author cannot open', function (): void {
        $hiddenInstitution = Institution::factory()->create(['tenant_id' => $this->otherTenant->id]);
        $meeting = Meeting::create(['title' => 'Uždaras posėdis', 'start_time' => now()->subDay(), 'type' => MeetingType::InPerson]);
        $meeting->institutions()->attach($hiddenInstitution->id);
        $agendaItem = AgendaItem::create(['meeting_id' => $meeting->id, 'title' => 'Slaptas klausimas', 'order' => 1]);

        asUser($this->editor)->post(route('goals.steps.store', $this->goal), [
            'title' => ['lt' => 'Veiksmas'],
            'happened_on' => '2026-09-01',
            'agenda_item_id' => $agendaItem->id,
        ])->assertSessionHasErrors('agenda_item_id');
    });
});

describe('agenda items', function (): void {
    beforeEach(function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->meeting = Meeting::create(['title' => 'Tarybos posėdis', 'start_time' => '2026-09-15 14:00', 'type' => MeetingType::InPerson]);
        $this->meeting->institutions()->attach($institution->id);
        $this->agendaItem = AgendaItem::create(['meeting_id' => $this->meeting->id, 'title' => 'Bendrabučių tvarka', 'order' => 1]);
        $this->editor->duties()->first()->institution()->associate($institution)->save();
    });

    test('agenda item links and options omit disabled goals even for super admins', function (): void {
        $goal = Goal::factory()->create(['tenant_id' => $this->otherTenant->id]);
        $step = Step::factory()->create(['goal_id' => $goal->id, 'agenda_item_id' => $this->agendaItem->id]);
        $admin = makeAdminUser($this->tenant);

        asUser($admin)->get(route('agendaItems.show', $this->agendaItem))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('goalLinks.goals', 0)->has('goalLinks.options', 1)->where('goalLinks.options.0.id', $this->goal->id)));
        asUser($admin)->post(route('agendaItems.goals.store', $this->agendaItem), ['goal_id' => $goal->id])->assertForbidden();
        asUser($admin)->delete(route('agendaItems.goals.destroy', ['agendaItem' => $this->agendaItem, 'goal' => $goal]))->assertNotFound();

        expect($step->fresh())->not->toBeNull();
    });

    test('adding an agenda item to a goal logs a step dated by its meeting, and removing it takes the step away', function (): void {
        asUser($this->editor)->post(route('agendaItems.goals.store', $this->agendaItem), ['goal_id' => $this->goal->id])
            ->assertRedirect()->assertSessionHasNoErrors();

        $step = $this->goal->steps()->sole();

        expect($step->agenda_item_id)->toBe($this->agendaItem->id)
            ->and($step->happened_on->toDateString())->toBe('2026-09-15');

        asUser($this->editor)->get(route('agendaItems.show', $this->agendaItem))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goalsExperiment', true)
                ->missing('goalLinks')
                ->loadDeferredProps(fn (Assert $reload) => $reload->where('goalLinks.goals.0.id', $this->goal->id)));

        asUser($this->editor)->delete(route('agendaItems.goals.destroy', ['agendaItem' => $this->agendaItem, 'goal' => $this->goal]))
            ->assertRedirect();

        expect($this->goal->steps()->count())->toBe(0);
    });

    test('only the goal\'s editors add agenda items to it', function (): void {
        $foreignGoal = Goal::factory()->create(['tenant_id' => $this->otherTenant->id]);

        asUser($this->editor)->post(route('agendaItems.goals.store', $this->agendaItem), ['goal_id' => $foreignGoal->id])
            ->assertForbidden();
    });
});

test('the goal\'s change history can be read', function (): void {
    $this->goal->update(['status' => GoalStatus::InProgress]);

    asUser($this->editor)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'goal', 'subjectId' => $this->goal->id]))
        ->assertOk()
        ->assertJsonPath('data.0.event', 'updated');
});
