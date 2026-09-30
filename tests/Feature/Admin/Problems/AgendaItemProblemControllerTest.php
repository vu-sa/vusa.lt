<?php

use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    [$this->tenant, $this->otherTenant] = Tenant::query()->inRandomOrder()->take(2)->get();

    $this->coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);

    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
    $meeting = Meeting::create(['title' => 'Tarybos posėdis', 'start_time' => now()->subDay(), 'type' => MeetingType::InPerson]);
    $meeting->institutions()->attach($this->institution->id);
    $this->agendaItem = AgendaItem::create(['meeting_id' => $meeting->id, 'title' => 'Bendrabučiai', 'order' => 1]);

    $this->problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);
});

test('linking a problem also links the meeting\'s institution and shows on both records', function (): void {
    asUser($this->coordinator)
        ->post(route('agendaItems.problems.store', $this->agendaItem), ['problem_id' => $this->problem->id])
        ->assertRedirect();

    expect($this->agendaItem->problems()->pluck('problems.id')->all())->toBe([$this->problem->id])
        ->and($this->problem->institutions()->pluck('institutions.id')->all())->toBe([$this->institution->id]);

    asUser($this->coordinator)->get(route('agendaItems.show', $this->agendaItem))
        ->assertInertia(fn (Assert $page) => $page->where('problems.0.id', $this->problem->id));

    asUser($this->coordinator)->get(route('problems.show', $this->problem))
        ->assertInertia(fn (Assert $page) => $page->where('agendaItems.0.id', $this->agendaItem->id));
});

test('a problem from another padalinys links without taking the meeting\'s institution', function (): void {
    $foreign = Problem::factory()->create(['tenant_id' => $this->otherTenant->id]);

    asUser($this->coordinator)
        ->post(route('agendaItems.problems.store', $this->agendaItem), ['problem_id' => $foreign->id])
        ->assertRedirect();

    expect($this->agendaItem->problems()->count())->toBe(1)
        ->and($foreign->institutions()->count())->toBe(0);
});

test('only someone who may edit the agenda item links or unlinks', function (): void {
    $member = makeUser($this->tenant);

    asUser($member)
        ->post(route('agendaItems.problems.store', $this->agendaItem), ['problem_id' => $this->problem->id])
        ->assertForbidden();

    $this->agendaItem->problems()->attach($this->problem);

    asUser($member)
        ->delete(route('agendaItems.problems.destroy', [$this->agendaItem, $this->problem]))
        ->assertForbidden();

    asUser($this->coordinator)
        ->delete(route('agendaItems.problems.destroy', [$this->agendaItem, $this->problem]))
        ->assertRedirect();

    expect($this->agendaItem->problems()->count())->toBe(0);
});

test('a problem reader does not see agenda items of meetings they cannot open', function (): void {
    $this->agendaItem->problems()->attach($this->problem);

    asUser(makeUser($this->otherTenant))->get(route('problems.show', $this->problem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('agendaItems', []));
});
