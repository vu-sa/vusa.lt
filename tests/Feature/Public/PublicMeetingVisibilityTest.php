<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();

    $this->vusaType = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->institution->types()->attach($this->vusaType);

    $this->meeting = Meeting::factory()->create(['start_time' => now()->addDays(3)]);
    $this->meeting->institutions()->attach($this->institution);

    AgendaItem::factory()->for($this->meeting)->create([
        'title' => 'Dėl VU SA veiklos plano',
        'order' => 1,
        'type' => AgendaItemType::Voting->value,
        'start_time' => '18:30',
    ]);
});

function publicMeetingUrl(Meeting $meeting): string
{
    return route('publicMeetings.show', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'meetingsString' => 'posedziai',
        'meeting' => $meeting->id,
    ]);
}

function publishEventFor(Meeting $meeting, Tenant $tenant): Calendar
{
    return Calendar::factory()->for($tenant)->create([
        'meeting_id' => $meeting->id,
        'is_draft' => false,
        'date' => $meeting->start_time,
    ]);
}

test('a VU SA meeting is not public on its own', function (): void {
    $this->get(publicMeetingUrl($this->meeting))->assertNotFound();
});

test('a draft announcement does not publish the meeting', function (): void {
    Calendar::factory()->for($this->tenant)->create([
        'meeting_id' => $this->meeting->id,
        'is_draft' => true,
    ]);

    $this->get(publicMeetingUrl($this->meeting))->assertNotFound();
});

test('a published announcement does not, by itself, open the meeting page — settings-only', function (): void {
    publishEventFor($this->meeting, $this->tenant);

    $this->get(publicMeetingUrl($this->meeting))->assertNotFound();
});

test('the meeting stays public through the type list even without an announcement', function (): void {
    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => [$this->vusaType->id],
    ])->save();

    $this->get(publicMeetingUrl($this->meeting))->assertOk();
});

test('published documents reach the public meeting page, unshared ones do not', function (): void {
    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => [$this->vusaType->id],
    ])->save();

    Document::factory()->for($this->institution)->create([
        'meeting_id' => $this->meeting->id,
        'title' => 'VU SA Parlamento protokolas',
        'anonymous_url' => 'https://example.test/protokolas.pdf',
    ]);

    Document::factory()->for($this->institution)->create([
        'meeting_id' => $this->meeting->id,
        'title' => 'Neviešintas priedas',
        'anonymous_url' => null,
    ]);

    $this->get(publicMeetingUrl($this->meeting))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents', 1)
            ->where('documents.0.title', 'VU SA Parlamento protokolas'));
});

test('the calendar event page carries the meeting agenda regardless of settings, but hides the meeting-page link', function (): void {
    $event = publishEventFor($this->meeting, $this->tenant);

    Document::factory()->for($this->institution)->create([
        'meeting_id' => $this->meeting->id,
        'anonymous_url' => 'https://example.test/nutarimas.pdf',
    ]);

    $this->get(route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $event->date->format('Y'),
        'month' => $event->date->format('m'),
        'day' => $event->date->format('d'),
        'slug' => Str::slug($event->title),
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/CalendarEvent')
            ->has('meeting.agenda_items', 1)
            ->has('meeting.documents', 1)
            ->where('meeting.requires_student_perspective', false)
            // VU SA's own type is not on the settings list, so the meeting page/search entry
            // stay unreachable even though the announcement itself is public.
            ->where('meeting.is_publicly_visible', false));
});

test('the calendar event page links to the meeting page once settings make it reachable', function (): void {
    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => [$this->vusaType->id],
    ])->save();

    $event = publishEventFor($this->meeting, $this->tenant);

    $this->get(route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $event->date->format('Y'),
        'month' => $event->date->format('m'),
        'day' => $event->date->format('d'),
        'slug' => Str::slug($event->title),
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('meeting.is_publicly_visible', true));
});

test('the calendar event page links to sibling announcements for the same institution', function (): void {
    $event = publishEventFor($this->meeting, $this->tenant);

    $earlierMeeting = Meeting::factory()->create(['start_time' => now()->subDays(10)]);
    $earlierMeeting->institutions()->attach($this->institution);
    $earlierEvent = publishEventFor($earlierMeeting, $this->tenant);

    $laterMeeting = Meeting::factory()->create(['start_time' => now()->addDays(10)]);
    $laterMeeting->institutions()->attach($this->institution);
    $laterEvent = publishEventFor($laterMeeting, $this->tenant);

    // A different institution's announcement must never show up as a sibling.
    $otherInstitution = Institution::factory()->for($this->tenant)->create();
    $otherMeeting = Meeting::factory()->create(['start_time' => now()->addDays(1)]);
    $otherMeeting->institutions()->attach($otherInstitution);
    publishEventFor($otherMeeting, $this->tenant);

    $this->get(route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $event->date->format('Y'),
        'month' => $event->date->format('m'),
        'day' => $event->date->format('d'),
        'slug' => Str::slug($event->title),
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('previousMeetingEvent.id', $earlierEvent->id)
            ->where('nextMeetingEvent.id', $laterEvent->id));
});

test('an ordinary event carries no meeting', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create(['is_draft' => false]);

    $this->get(route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $event->date->format('Y'),
        'month' => $event->date->format('m'),
        'day' => $event->date->format('d'),
        'slug' => Str::slug($event->title),
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('meeting', null));
});
