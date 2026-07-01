<?php

use App\Enums\MeetingType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'Page access meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);

    $duty = Duty::factory()->for($this->institution)->create();
    $this->viewer = User::factory()->create();
    $this->viewer->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => null]);

    $this->outsider = makeUser(
        Tenant::query()->where('id', '!=', $this->tenant->id)->inRandomOrder()->first() ?? $this->tenant
    );
});

test('a coordinator opens the agenda item page with canUpdate true', function () {
    asUser($this->coordinator)
        ->get(route('agendaItems.edit', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/EditAgendaItem')
            ->where('canUpdate', true)
        );
});

test('a view-only participant opens the page read-only (canUpdate false)', function () {
    asUser($this->viewer)
        ->get(route('agendaItems.edit', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/EditAgendaItem')
            ->where('canUpdate', false)
        );
});

test('an outsider cannot open the page (403)', function () {
    asUser($this->outsider)
        ->get(route('agendaItems.edit', $this->agendaItem))
        ->assertStatus(403);
});

test('a view-only participant cannot persist updates (403)', function () {
    asUser($this->viewer)
        ->put(route('agendaItems.update', $this->agendaItem), ['title' => 'Hijacked'])
        ->assertStatus(403);
});
