<?php

use App\Enums\CalendarHeroStyleEnum;
use App\Enums\InstitutionScope;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
    // A VU SA body: only those are announced in the calendar, so anything exercising the
    // announcement path has to be one. An institution with no types resolves to external.
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->institution->types()->attach(Type::factory()->forInstitutions(InstitutionScope::Vusa)->create());

    $this->meeting = Meeting::factory()->create(['start_time' => now()->addWeek()]);
    $this->meeting->institutions()->attach($this->institution);
});

/** A meeting of a body VU SA only delegates into, which it has no standing to announce. */
function externalMeeting(Tenant $tenant): Meeting
{
    $institution = Institution::factory()->for($tenant)->create();
    $institution->types()->attach(Type::factory()->forInstitutions(InstitutionScope::University)->create());

    $meeting = Meeting::factory()->create(['start_time' => now()->addWeek()]);
    $meeting->institutions()->attach($institution);

    return $meeting;
}

test('an external body\'s meeting cannot be announced', function (): void {
    $meeting = externalMeeting($this->tenant);

    asUser($this->admin)
        ->post(route('meetings.calendarEvent.store', $meeting))
        ->assertStatus(403);

    expect($meeting->fresh()->calendarEvent)->toBeNull();
});

test('an external body\'s meeting cannot adopt an existing event either', function (): void {
    $meeting = externalMeeting($this->tenant);
    $event = Calendar::factory()->for($this->tenant)->create();

    asUser($this->admin)
        ->post(route('meetings.calendarEvent.store', $meeting), ['calendar_id' => $event->id])
        ->assertStatus(403);

    expect($event->fresh()->meeting_id)->toBeNull();
});

test('announcing a meeting creates a draft event in the institution tenant', function (): void {
    asUser($this->admin)
        ->post(route('meetings.calendarEvent.store', $this->meeting))
        ->assertRedirect();

    $event = $this->meeting->fresh()->calendarEvent;

    expect($event)->not->toBeNull()
        ->and($event->is_draft)->toBeTrue()
        ->and($event->tenant_id)->toBe($this->tenant->id)
        // A posėdis has no hero photo; the loud variants only look empty.
        ->and($event->hero_style)->toBe(CalendarHeroStyleEnum::MINIMAL)
        ->and($event->date->toDateTimeString())->toBe($this->meeting->start_time->toDateTimeString());
});

test('the calendar form is told which meeting the event announces', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create(['meeting_id' => $this->meeting->id]);

    asUser($this->admin)
        ->get(route('calendar.edit', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('meeting.id', $this->meeting->id)
            ->where('meeting.trashed', false)
            ->has('meeting.institution_name'));
});

test('an ordinary calendar event is told about no meeting', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create();

    asUser($this->admin)
        ->get(route('calendar.edit', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('meeting', null));
});

test('a meeting can only be announced once', function (): void {
    asUser($this->admin)->post(route('meetings.calendarEvent.store', $this->meeting));

    asUser($this->admin)
        ->post(route('meetings.calendarEvent.store', $this->meeting))
        ->assertStatus(409);

    expect(Calendar::where('meeting_id', $this->meeting->id)->count())->toBe(1);
});

test('an existing event can be adopted as the announcement', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create(['date' => $this->meeting->start_time]);

    asUser($this->admin)
        ->post(route('meetings.calendarEvent.store', $this->meeting), ['calendar_id' => $event->id])
        ->assertRedirect();

    expect($event->fresh()->meeting_id)->toBe($this->meeting->id);
});

test('an event already spoken for cannot be adopted', function (): void {
    $otherMeeting = Meeting::factory()->create();
    $event = Calendar::factory()->for($this->tenant)->create(['meeting_id' => $otherMeeting->id]);

    asUser($this->admin)
        ->post(route('meetings.calendarEvent.store', $this->meeting), ['calendar_id' => $event->id])
        ->assertSessionHasErrors('calendar_id');

    expect($event->fresh()->meeting_id)->toBe($otherMeeting->id);
});

test('a user without meeting update rights cannot announce', function (): void {
    $user = makeUser($this->tenant);

    asUser($user)
        ->post(route('meetings.calendarEvent.store', $this->meeting))
        ->assertStatus(403);

    expect($this->meeting->fresh()->calendarEvent)->toBeNull();
});

test('unlinking keeps the event itself', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create(['meeting_id' => $this->meeting->id]);

    asUser($this->admin)
        ->delete(route('meetings.calendarEvent.destroy', $this->meeting))
        ->assertRedirect();

    expect($event->fresh())->not->toBeNull()
        ->and($event->fresh()->meeting_id)->toBeNull();
});

test('moving the meeting moves its announcement', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create(['meeting_id' => $this->meeting->id]);

    $newStart = now()->addWeeks(3)->startOfMinute();
    $this->meeting->update(['start_time' => $newStart]);

    expect($event->fresh()->date->toDateTimeString())->toBe($newStart->toDateTimeString());
});

test('moving the meeting flushes the calendar caches the event feeds read', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create(['meeting_id' => $this->meeting->id]);

    // The event has to be saved through the model, not `$relation->update()`, or Calendar's
    // `saved` hooks never fire and the iCal feed keeps serving the old date.
    $observed = false;
    Calendar::saved(function () use (&$observed): void {
        $observed = true;
    });

    $this->meeting->update(['start_time' => now()->addWeeks(4)->startOfMinute()]);

    expect($observed)->toBeTrue()
        ->and($event->fresh()->date->toDateTimeString())
        ->toBe($this->meeting->fresh()->start_time->toDateTimeString());
});

test('editing the announcement does not move the meeting', function (): void {
    // The meeting owns the timing; the announcement follows it, never the other way round.
    $event = Calendar::factory()->for($this->tenant)->create([
        'meeting_id' => $this->meeting->id,
        'date' => $this->meeting->start_time,
    ]);

    $originalStart = $this->meeting->start_time->toDateTimeString();

    $event->update(['date' => now()->addMonths(2)->startOfMinute()]);

    expect($this->meeting->fresh()->start_time->toDateTimeString())->toBe($originalStart);
});

test('the calendar form cannot move an event that announces a meeting', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create([
        'meeting_id' => $this->meeting->id,
        'date' => $this->meeting->start_time,
    ]);

    asUser($this->admin)->patch(route('calendar.update', $event), [
        'title' => ['lt' => 'Pakeista', 'en' => 'Changed'],
        'date' => now()->addMonths(2)->format('Y-m-d H:i:s'),
        'tenant_id' => $this->tenant->id,
    ])->assertSessionHasNoErrors();

    // The rest of the payload went through, so the date being ignored is the rule working.
    expect($event->fresh()->getTranslation('title', 'lt'))->toBe('Pakeista')
        ->and($event->fresh()->date->toDateTimeString())
        ->toBe($this->meeting->start_time->toDateTimeString());
});

test('an ordinary event can still be moved from the calendar form', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create();
    $newDate = now()->addMonths(2)->startOfMinute();

    asUser($this->admin)->patch(route('calendar.update', $event), [
        'title' => ['lt' => 'Renginys', 'en' => 'Event'],
        'date' => $newDate->format('Y-m-d H:i:s'),
        'tenant_id' => $this->tenant->id,
    ])->assertSessionHasNoErrors();

    expect($event->fresh()->date->toDateTimeString())->toBe($newDate->toDateTimeString());
});

test('publishing an event does not, by itself, make a VU SA meeting publicly visible', function (): void {
    // Settings-only — see Meeting::isPubliclyVisible(). Publishing the event still shows the
    // agenda inline on the event page (PublicPageController::meetingBehind()), but does not open
    // the meeting page/search entry unless the institution's type is on MeetingSettings.
    expect($this->meeting->fresh()->isPubliclyVisible())->toBeFalse();

    $event = Calendar::factory()->for($this->tenant)->create([
        'meeting_id' => $this->meeting->id,
        'is_draft' => true,
    ]);

    expect($this->meeting->fresh()->isPubliclyVisible())->toBeFalse();

    $event->update(['is_draft' => false]);

    expect($this->meeting->fresh()->isPubliclyVisible())->toBeFalse();
});

test('the admin meeting page renders the linked event and the documents tab', function (): void {
    Calendar::factory()->for($this->tenant)->create(['meeting_id' => $this->meeting->id]);
    Document::factory()->for($this->institution)->create(['meeting_id' => $this->meeting->id]);

    asUser($this->admin)
        ->get(route('meetings.show', $this->meeting))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Representation/ShowMeeting')
            ->has('meeting.calendar_event')
            ->has('meeting.documents', 1)
            ->where('governanceScope', InstitutionScope::Vusa->value));
});

test('meeting_id cannot be set through the ordinary calendar form', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create();

    asUser($this->admin)->patch(route('calendar.update', $event), [
        'title' => ['lt' => 'Pakeista', 'en' => 'Changed'],
        'date' => now()->addDay()->format('Y-m-d H:i:s'),
        'tenant_id' => $this->tenant->id,
        'meeting_id' => $this->meeting->id,
    ])->assertSessionHasNoErrors();

    // The rest of the payload went through, so the omission is the rule doing its job.
    expect($event->fresh()->getTranslation('title', 'lt'))->toBe('Pakeista')
        ->and($event->fresh()->meeting_id)->toBeNull();
});
