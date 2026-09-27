<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Resource;
use App\Models\Task;
use App\Models\Tenant;
use App\Support\Permissions\BaselineAccess;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

/**
 * Each line of BaselineAccess, checked for a member who holds a duty but no role at all.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->member = makeUser($this->tenant);
    $this->duty = $this->member->duties()->firstOrFail();
    $this->institution = $this->duty->institution;
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
});

test('every baseline line is written out in both languages', function (): void {
    foreach (['lt', 'en'] as $locale) {
        app()->setLocale($locale);

        expect(BaselineAccess::descriptions())->toHaveKeys(BaselineAccess::RESOURCES)
            ->each->not->toStartWith('access.baseline.');
    }
});

test('sees the institution list, their own institution in full and others as their public side', function (): void {
    $other = Institution::factory()->for($this->otherTenant)->create(['is_active' => true]);

    asUser($this->member)->get(route('institutions.index'))->assertOk();
    asUser($this->member)->get(route('institutions.show', $this->institution))
        ->assertInertia(fn (Assert $page) => $page->where('readOnly', false));
    asUser($this->member)->get(route('institutions.show', $other))
        ->assertInertia(fn (Assert $page) => $page->where('readOnly', true));
});

test('sees and edits their institution\'s meetings of their term, and those meetings\' agenda items after it ends', function (): void {
    $meeting = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => now()]);
    $item = AgendaItem::factory()->for($meeting)->create();

    asUser($this->member)->get(route('meetings.show', $meeting))
        ->assertInertia(fn (Assert $page) => $page->where('abilities.update', true));

    $this->duty->pivot->update(['end_date' => now()->addDay()]);
    $this->travel(1)->month();

    asUser($this->member)->get(route('meetings.show', $meeting))->assertOk();
    asUser($this->member)->get(route('agendaItems.show', $item))->assertOk();
});

test('browses every padalinys\' problems and every resource', function (): void {
    $problem = Problem::factory()->create(['tenant_id' => $this->otherTenant->id]);
    $resource = Resource::factory()->for($this->otherTenant)->create();

    asUser($this->member)->get(route('problems.index'))->assertOk();
    asUser($this->member)->get(route('problems.show', $problem))->assertOk();
    asUser($this->member)->get(route('resources.index'))->assertOk();
    asUser($this->member)->get(route('resources.show', $resource))->assertOk();
});

test('opens their current and past duties, not other people\'s', function (): void {
    $pastDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $this->member->duties()->attach($pastDuty, ['start_date' => now()->subYears(2), 'end_date' => now()->subYear()]);
    $strangersDuty = Duty::factory()->for($this->institution)->create();

    asUser($this->member)->get(route('duties.show', $this->duty))->assertOk();
    asUser($this->member)->get(route('duties.show', $pastDuty))->assertOk();
    asUser($this->member)->get(route('duties.show', $strangersDuty))->assertForbidden();
});

test('completes a task assigned to them but cannot delete it', function (): void {
    $task = Task::factory()->forMeeting(Meeting::factory()->hasAttached($this->institution)->create())
        ->create(['action_type' => ActionType::Manual]);
    $task->users()->attach($this->member);

    asUser($this->member)
        ->post(route('tasks.updateCompletionStatus', $task), ['completed' => true])
        ->assertSessionHasNoErrors();
    asUser($this->member)->delete(route('tasks.destroy', $task))->assertForbidden();

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('comments on a record they can see', function (): void {
    asUser($this->member)
        ->postJson(route('api.v1.admin.comments.store', ['commentableType' => 'institution', 'commentableId' => $this->institution->id]), ['body' => '<p>Kada kitas posėdis?</p>'])
        ->assertCreated();
});
