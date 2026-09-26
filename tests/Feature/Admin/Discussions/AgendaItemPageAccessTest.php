<?php

use App\Enums\MeetingType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Settings\MeetingSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
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

test('a coordinator opens the agenda item page with the outcome controls live', function (): void {
    asUser($this->coordinator)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('abilities.update', true)
            ->where('publicUrl', null)
        );
});

test('a public agenda item links to its meeting on the institution subdomain', function (): void {
    $type = Type::factory()->forInstitutions()->create();
    $this->institution->types()->attach($type);
    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => [$type->id],
    ])->save();

    asUser($this->coordinator)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('publicUrl', route('publicMeetings.show', [
                'subdomain' => $this->tenant->subdomain(),
                'lang' => app()->getLocale(),
                'meeting' => $this->meeting,
            ]))
        );
});

test('a view-only participant opens the page read-only', function (): void {
    asUser($this->viewer)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('abilities.update', false)
        );
});

test('an outsider cannot open the page (403)', function (): void {
    asUser($this->outsider)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertStatus(403);
});

test('a view-only participant cannot persist updates (403)', function (): void {
    asUser($this->viewer)
        ->put(route('agendaItems.update', $this->agendaItem), ['title' => 'Hijacked'])
        ->assertStatus(403);
});
