<?php

use App\Models\Goal;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Step;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', '!=', 'vusa')->first();

    $this->tenant->update(['goals_enabled' => true]);

    $this->goal = Goal::factory()->public()->create(['tenant_id' => $this->tenant->id]);
    Step::factory()->create(['goal_id' => $this->goal->id, 'title' => ['lt' => 'Pateiktas raštas', 'en' => 'Letter sent']]);
});

function goalUrl(Tenant $tenant, ?Goal $goal = null): string
{
    return $goal === null
        ? route('publicGoals.index', ['subdomain' => $tenant->subdomain(), 'lang' => 'lt', 'goalsString' => 'tikslai'])
        : route('publicGoals.show', ['subdomain' => $tenant->subdomain(), 'lang' => 'lt', 'goalsString' => 'tikslai', 'goal' => $goal->id]);
}

test('a pilot padalinys lists and shows its public goals with their steps', function (): void {
    Goal::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->get(goalUrl($this->tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Public/Goals/IndexGoals')->has('goals', 1));

    $this->get(goalUrl($this->tenant, $this->goal))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Goals/ShowGoal')
            ->where('goal.id', $this->goal->id)
            ->where('steps.0.title', 'Pateiktas raštas'));
});

test('an internal goal, or one of another padalinys, is not found', function (): void {
    $internal = Goal::factory()->create(['tenant_id' => $this->tenant->id]);
    $foreign = Goal::factory()->public()->create(['tenant_id' => Tenant::query()->whereKeyNot($this->tenant->id)->where('alias', '!=', 'vusa')->value('id')]);

    $this->get(goalUrl($this->tenant, $internal))->assertNotFound();
    $this->get(goalUrl($this->tenant, $foreign))->assertNotFound();
});

test('the pages do not exist outside the pilot', function (): void {
    $this->tenant->update(['goals_enabled' => false]);

    $this->get(goalUrl($this->tenant))->assertNotFound();
    $this->get(goalUrl($this->tenant, $this->goal))->assertNotFound();
});

test('database enrollment changes apply on the next visit and preserve public goals', function (): void {
    $this->get(goalUrl($this->tenant, $this->goal))->assertOk();

    Tenant::query()->whereKey($this->tenant->id)->update(['goals_enabled' => false]);

    $this->get(goalUrl($this->tenant))->assertNotFound();
    $this->get(goalUrl($this->tenant, $this->goal))->assertNotFound();

    Tenant::query()->whereKey($this->tenant->id)->update(['goals_enabled' => true]);

    $this->get(goalUrl($this->tenant, $this->goal))->assertOk();
    expect($this->goal->fresh())->not->toBeNull();
});

test('a step linked to an internal-only agenda item is excluded from public goals and their counts', function (): void {
    $item = AgendaItem::factory()->for(Meeting::factory())->create(['is_private' => true]);
    Step::factory()->create([
        'goal_id' => $this->goal->id, 'agenda_item_id' => $item->id,
        'title' => ['lt' => 'Vidaus klausimo kopija', 'en' => 'Copied internal item'],
    ]);

    $this->get(goalUrl($this->tenant))->assertInertia(fn (Assert $page) => $page->where('goals.0.steps_count', 1));
    $this->get(goalUrl($this->tenant, $this->goal))->assertInertia(fn (Assert $page) => $page->has('steps', 1))
        ->assertDontSee('Vidaus klausimo kopija');
});
