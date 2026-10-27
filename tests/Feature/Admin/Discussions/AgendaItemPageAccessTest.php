<?php

use App\Enums\MeetingType;
use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\MeetingSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'Page access meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);

    // Sees the meeting through a linked institution; members of the meeting's own institution edit it.
    $linkedInstitution = Institution::factory()->for($this->tenant)->create();
    InstitutionLink::factory()->create(['source_institution_id' => $linkedInstitution->id, 'target_institution_id' => $this->institution->id]);
    $duty = Duty::factory()->for($linkedInstitution)->create();
    $this->viewer = User::factory()->create();
    $this->viewer->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => null]);

    $this->outsider = makeUser(
        Tenant::query()->where('id', '!=', $this->tenant->id)->inRandomOrder()->first() ?? $this->tenant
    );
});

test('a coordinator opens the agenda item page with the outcome controls live', function (): void {
    asUserWithInertia($this->coordinator)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('abilities.update', true)
            ->where('publicVisibility.mode', 'internal')
        );
});

test('a public agenda item links to its meeting on the institution subdomain', function (): void {
    $type = InstitutionType::factory()->withGovernanceScope()->create();
    $this->institution->types()->attach($type);
    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => [$type->id],
    ])->save();

    asUserWithInertia($this->coordinator)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('publicVisibility.url', route('publicMeetings.show', [
                'subdomain' => $this->tenant->subdomain(),
                'lang' => app()->getLocale(),
                'meeting' => $this->meeting,
            ]))
        );
});

test('an agenda item of a meeting announced in the calendar links the event page', function (): void {
    $event = Calendar::factory()->create(['tenant_id' => $this->tenant->id, 'meeting_id' => $this->meeting->id, 'is_draft' => false]);

    asUserWithInertia($this->coordinator)
        ->get(route('agendaItems.show', $this->agendaItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('publicVisibility.mode', 'calendar_event')
            ->where('publicVisibility.url', $event->publicUrl(app()->getLocale()))
        );
});

test('a view-only participant opens the page read-only', function (): void {
    asUserWithInertia($this->viewer)
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
