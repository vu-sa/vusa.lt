<?php

use App\Enums\AgendaItemType;
use App\Http\Resources\InstitutionMeetingResource;
use App\Http\Resources\StepResource;
use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Step;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Services\AgendaItemPresenter;
use App\Services\MeetingCompletionService;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $type = InstitutionType::factory()->withGovernanceScope()->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->institution->types()->attach($type);
    app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$type->id]])->save();

    $this->meeting = Meeting::factory()->create(['start_time' => now()->addDay()]);
    $this->meeting->institutions()->attach($this->institution);
    $this->coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->outsider = makeUser(Tenant::factory()->create());
    $this->item = AgendaItem::factory()->for($this->meeting)->create([
        'title' => ['lt' => 'Originalus neviešas pavadinimas', 'en' => 'Original internal title'],
        'description' => ['lt' => 'Neviešo turinio žymuo', 'en' => 'Internal content marker'],
        'student_position' => ['lt' => 'Nevieša pozicija', 'en' => 'Internal position'],
        'type' => AgendaItemType::Voting,
        'is_private' => true,
        'public_title' => ['lt' => 'Darbo klausimas', 'en' => 'Working item'],
        'order' => 2,
        'start_time' => '14:00',
    ]);
    Vote::factory()->for($this->item, 'agendaItem')->create([
        'is_main' => true, 'decision' => 'positive', 'student_vote' => 'positive', 'student_benefit' => 'positive',
    ]);
});

test('public pages receive only the agenda placeholder even for an internal reader', function (bool $signedIn): void {
    if ($signedIn) {
        asUser($this->coordinator);
    }

    $response = $this->get(route('publicMeetings.show', ['subdomain' => 'www', 'lang' => 'lt', 'meeting' => $this->meeting]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('meeting.agenda_items.0', [
            'id' => $this->item->id, 'order' => 2, 'is_private' => true, 'is_redacted' => true, 'title' => 'Darbo klausimas',
        ])
    );
    $response->assertDontSee('Originalus neviešas pavadinimas')->assertDontSee('Neviešo turinio žymuo');
})->with([false, true]);

test('calendar announcements redact items even without a public meeting page', function (): void {
    app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => []])->save();
    $event = Calendar::factory()->for($this->tenant)->create(['meeting_id' => $this->meeting->id, 'is_draft' => false]);

    $this->get(route('calendar.show', [
        'subdomain' => 'www', 'lang' => 'lt', 'year' => $event->date->format('Y'), 'permalink' => $event->getTranslation('permalink', 'lt'),
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('meeting.agenda_items.0.is_redacted', true)
        ->missing('meeting.agenda_items.0.description')
        ->missing('meeting.agenda_items.0.start_time')
        ->missing('meeting.agenda_items.0.main_vote')
    );
});

test('a public-only reader gets a minimal direct record and redacted siblings', function (): void {
    asUser($this->outsider)->get(route('agendaItems.show', $this->item))
        ->assertInertia(fn (Assert $page) => $page
            ->where('agendaItem', AgendaItemPresenter::redacted($this->item))
            ->where('isRedacted', true)
            ->where('abilities.update', false)
            ->missing('problems')
            ->missing('goalLinks')
            ->missing('agendaItem.meeting')
        );

    asUser($this->outsider)->get(route('meetings.show', $this->meeting))
        ->assertInertia(fn (Assert $page) => $page
            ->where('meeting.agenda_items.0.is_redacted', true)
            ->missing('meeting.agenda_items.0.votes')
            ->where('completion.status', null)
        );
});

test('coordinators and members at the meeting date retain their existing full access', function (): void {
    $member = User::factory()->create();
    $duty = Duty::factory()->for($this->institution)->create();
    $member->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => null]);

    foreach ([$this->coordinator, $member] as $reader) {
        asUser($reader)->get(route('agendaItems.show', $this->item))
            ->assertInertia(fn (Assert $page) => $page
                ->where('isRedacted', false)
                ->where('agendaItem.title.lt', 'Originalus neviešas pavadinimas')
                ->where('readOnly', false)
            );
    }
});

test('editors can change visibility and translated public titles without losing content', function (): void {
    asUser($this->coordinator)->patch(route('agendaItems.update', $this->item), [
        'is_private' => false, 'public_title' => ['lt' => 'Viešas darbo klausimas', 'en' => 'Public working item'],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($this->item->fresh()->is_private)->toBeFalse()
        ->and($this->item->fresh()->getTranslation('public_title', 'en'))->toBe('Public working item')
        ->and($this->item->fresh()->getTranslation('title', 'lt'))->toBe('Originalus neviešas pavadinimas');
});

test('public-only readers cannot change privacy or store a public title', function (): void {
    asUser($this->outsider)->patch(route('agendaItems.update', $this->item), ['is_private' => false])
        ->assertForbidden();
    expect($this->item->fresh()->is_private)->toBeTrue();
});

test('invalid privacy inputs are refused', function (): void {
    asUser($this->coordinator)->patch(route('agendaItems.update', $this->item), [
        'is_private' => 'secret', 'public_title' => ['lt' => str_repeat('x', 201)],
    ])->assertSessionHasErrors(['is_private', 'public_title.lt']);
    expect($this->item->fresh()->getTranslation('public_title', 'lt'))->toBe('Darbo klausimas');
});

test('creation saves per-item privacy before any public response', function (): void {
    asUser($this->coordinator)->post(route('agendaItems.store'), [
        'meeting_id' => $this->meeting->id,
        'agendaItemTitles' => ['Vidinis naujas punktas', 'Viešas naujas punktas'],
        'privateFlags' => [true, false],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $items = $this->meeting->agendaItems()->orderBy('order')->get();
    expect($items[1]->is_private)->toBeTrue()
        ->and($items[1]->toArray()['title'])->toBe(__('meetings.privacy.hidden_title'))
        ->and($items[2]->is_private)->toBeFalse();
});

test('public title fallback never uses the original title and completion stays internal', function (): void {
    $this->item->update(['public_title' => ['lt' => 'Tik LT', 'en' => '']]);
    app()->setLocale('en');
    expect(AgendaItemPresenter::redacted($this->item)['title'])->toBe('Tik LT');

    $this->item->update(['public_title' => null]);
    expect($this->item->toArray()['title'])->toBe(__('meetings.privacy.hidden_title'))
        ->and(app(MeetingCompletionService::class)->itemIsComplete($this->item->fresh(), true))->toBeTrue();
});

test('a failed search eviction refuses a restrictive save before changing the database', function (): void {
    $this->item->update(['is_private' => false]);
    $engine = Mockery::mock(NullEngine::class)->makePartial();
    $engine->shouldReceive('delete')->andThrow(new RuntimeException('Search unavailable'));
    app(EngineManager::class)->extend('typesense', fn () => $engine);
    app(EngineManager::class)->forgetDrivers();

    expect(fn () => $this->item->update(['is_private' => true]))->toThrow(RuntimeException::class, 'Search unavailable');
    expect($this->item->fresh()->is_private)->toBeFalse();
});

test('private relationships and copied step content do not disclose information to public-only readers', function (): void {
    $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->item->problems()->attach($problem);
    asUser($this->outsider)->get(route('problems.show', $problem))
        ->assertInertia(fn (Assert $page) => $page->where('agendaItems', []));

    $step = Step::factory()->create([
        'agenda_item_id' => $this->item->id,
        'title' => $this->item->getTranslations('title'),
        'description' => $this->item->getTranslations('description'),
    ])->load(StepResource::RELATIONS);
    $resource = (new StepResource($step))->resolve(request());
    expect(json_encode($resource))->not->toContain('Originalus neviešas pavadinimas', 'Neviešo turinio žymuo')
        ->and($resource['agenda_item'])->toBeNull();
});

test('shared meeting statistics omit private votes for public-only readers', function (): void {
    asUser($this->outsider);
    $resource = (new InstitutionMeetingResource($this->meeting->load('agendaItems.votes')))->resolve(request());
    expect($resource['vote_matches'])->toBe(0)->and($resource['agenda_item_titles'])->toBe(['Darbo klausimas']);

    asUser($this->coordinator);
    request()->setUserResolver(fn () => $this->coordinator);
    $resource = (new InstitutionMeetingResource($this->meeting))->resolve(request());
    expect($resource['vote_matches'])->toBe(1);
});

test('a public-only follower receives no private agenda completion metadata', function (): void {
    $this->outsider->followedInstitutions()->attach($this->institution);
    $this->item->update(['type' => null]);
    Notification::fake();
    $this->item->update(['type' => AgendaItemType::Informational]);

    Notification::assertNotSentTo(
        $this->outsider, MeetingAgendaCompletedNotification::class,
    );
});

test('meeting history cannot bypass a private agenda items own reading permission', function (): void {
    $reader = makeUser($this->tenant);
    $reader->givePermissionTo('meetings.read.padalinys');
    expect($reader->can('view', $this->meeting))->toBeTrue()
        ->and($reader->can('view', $this->item))->toBeFalse();

    asUser($reader)->getJson(route('api.v1.admin.activityLog.index', [
        'subjectType' => 'meeting', 'subjectId' => $this->meeting->id,
    ]))->assertOk()->assertDontSee('Originalus neviešas pavadinimas')
        ->assertDontSee('Neviešo turinio žymuo');
});
