<?php

use App\Actions\GetPublicMeetingDocuments;
use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\Vote;
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

    $this->meeting = Meeting::factory()->create(['start_time' => '2026-05-14 10:00:00']);
    $this->meeting->institutions()->attach($this->institution);

    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

/** The published announcement, which is what opens the agenda to the public. */
function announceTranslatedMeeting(Meeting $meeting, Tenant $tenant): Calendar
{
    return Calendar::factory()->for($tenant)->create([
        'meeting_id' => $meeting->id,
        'is_draft' => false,
        'date' => $meeting->start_time,
    ]);
}

function translatedCalendarEventUrl(Calendar $event, string $lang): string
{
    return route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => $lang,
        'year' => $event->date->format('Y'),
        'month' => $event->date->format('m'),
        'day' => $event->date->format('d'),
        'slug' => Str::slug($event->getTranslation('title', $lang)),
    ]);
}

describe('public agenda', function (): void {
    test('the English event page shows the English agenda', function (): void {
        AgendaItem::factory()->for($this->meeting)->create([
            'title' => ['lt' => 'Dėl veiklos plano', 'en' => 'On the activity plan'],
            'description' => ['lt' => 'Lietuviškas aprašymas', 'en' => 'English description'],
            'order' => 1,
            'type' => AgendaItemType::Voting->value,
        ]);

        $event = announceTranslatedMeeting($this->meeting, $this->tenant);

        $this->get(translatedCalendarEventUrl($event, 'en'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/CalendarEvent')
                ->where('meeting.agenda_items.0.title', 'On the activity plan')
                ->where('meeting.agenda_items.0.description', 'English description'));
    });

    test('an untranslated agenda item falls back to Lithuanian rather than rendering blank', function (): void {
        AgendaItem::factory()->for($this->meeting)->create([
            // No `en`: the overwhelmingly common case.
            'title' => ['lt' => 'Dėl veiklos plano'],
            'order' => 1,
        ]);

        $event = announceTranslatedMeeting($this->meeting, $this->tenant);

        $this->get(translatedCalendarEventUrl($event, 'en'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meeting.agenda_items.0.title', 'Dėl veiklos plano'));
    });

    test('the Lithuanian page is unaffected by an English translation', function (): void {
        AgendaItem::factory()->for($this->meeting)->create([
            'title' => ['lt' => 'Dėl veiklos plano', 'en' => 'On the activity plan'],
            'order' => 1,
        ]);

        $event = announceTranslatedMeeting($this->meeting, $this->tenant);

        $this->get(translatedCalendarEventUrl($event, 'lt'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meeting.agenda_items.0.title', 'Dėl veiklos plano'));
    });

    test('a vote title is localized alongside its agenda item', function (): void {
        $agendaItem = AgendaItem::factory()->for($this->meeting)->create([
            'title' => ['lt' => 'Klausimas', 'en' => 'Question'],
            'order' => 1,
            'type' => AgendaItemType::Voting->value,
        ]);

        Vote::factory()->main()->for($agendaItem, 'agendaItem')->create([
            'title' => ['lt' => 'Pagrindinis balsavimas', 'en' => 'Main vote'],
        ]);

        $event = announceTranslatedMeeting($this->meeting, $this->tenant);

        $this->get(translatedCalendarEventUrl($event, 'en'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meeting.agenda_items.0.main_vote.title', 'Main vote'));
    });
});

describe('public meeting documents', function (): void {
    beforeEach(function (): void {
        $this->publishDocument = fn (string $title, ?string $language) => Document::factory()
            ->for($this->institution)
            ->create([
                'meeting_id' => $this->meeting->id,
                'title' => $title,
                'language' => $language,
                'anonymous_url' => 'https://example.test/'.Str::slug($title).'.pdf',
            ]);
    });

    test('the English page shows only the English paperwork', function (): void {
        ($this->publishDocument)('Protokolas', 'Lietuvių');
        ($this->publishDocument)('Minutes', 'Anglų');

        app()->setLocale('en');

        expect(collect(GetPublicMeetingDocuments::execute($this->meeting))->pluck('title')->all())
            ->toBe(['Minutes']);
    });

    test('a document of unknown language is shown in either locale', function (): void {
        ($this->publishDocument)('Protokolas', 'Lietuvių');
        ($this->publishDocument)('Priedas', null);

        app()->setLocale('en');

        expect(collect(GetPublicMeetingDocuments::execute($this->meeting))->pluck('title')->all())
            ->toBe(['Priedas']);
    });

    test('a document date is a plain day, not a zoned timestamp', function (): void {
        ($this->publishDocument)('Protokolas', 'Lietuvių');

        // The column is a DATE; serializing it as "2026-07-23T00:00:00.000000Z" rendered in
        // full on the page and could shift the day by one for a westward reader.
        expect(GetPublicMeetingDocuments::execute($this->meeting)[0]['document_date'])
            ->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    });

    test('a meeting with no English paperwork still shows the Lithuanian files', function (): void {
        ($this->publishDocument)('Protokolas', 'Lietuvių');
        ($this->publishDocument)('Nutarimas', 'Lietuvių');

        app()->setLocale('en');

        $documents = collect(GetPublicMeetingDocuments::execute($this->meeting));

        // Hiding them outright would read as "this meeting produced nothing".
        expect($documents->pluck('title')->sort()->values()->all())->toBe(['Nutarimas', 'Protokolas'])
            ->and($documents->pluck('language_code')->unique()->all())->toBe(['lt']);
    });
});

describe('public meeting page title', function (): void {
    beforeEach(function (): void {
        app(MeetingSettings::class)->fill([
            'public_meeting_institution_type_ids' => [$this->vusaType->id],
        ])->save();
    });

    test('the head title is generated in the page locale', function (): void {
        $response = $this->get(route('publicMeetings.show', [
            'subdomain' => 'www',
            'lang' => 'en',
            // SetLocale redirects a URL carrying the other locale's slug, so name it here.
            'meetingsString' => 'meetings',
            'meeting' => $this->meeting->id,
        ]));

        $response
            ->assertOk();

        expect($response->getContent())->toMatch('/<title[^>]*>14 May 2026 10\.00 meeting/');
    });

    test('the Lithuanian head title keeps its long-standing wording', function (): void {
        $response = $this->get(route('publicMeetings.show', [
            'subdomain' => 'www',
            'lang' => 'lt',
            'meetingsString' => 'posedziai',
            'meeting' => $this->meeting->id,
        ]));

        $response
            ->assertOk();

        expect($response->getContent())->toMatch('/<title[^>]*>2026 gegužės 14 d\. 10\.00 val\. posėdis/');
    });
});

describe('admin write paths', function (): void {
    test('an update carrying both locales writes both', function (): void {
        $agendaItem = AgendaItem::factory()->for($this->meeting)->create(['order' => 1]);

        asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), [
                'title' => ['lt' => 'Lietuviškas', 'en' => 'English'],
                'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            ])
            ->assertSessionHasNoErrors();

        expect($agendaItem->fresh())
            ->toHaveTranslations('title')
            ->and($agendaItem->fresh()->getTranslation('title', 'en'))->toBe('English')
            ->and($agendaItem->fresh()->getTranslation('description', 'lt'))->toBe('Aprašymas');
    });

    test('a plain string is filed under Lithuanian even when the admin works in English', function (): void {
        $agendaItem = AgendaItem::factory()->for($this->meeting)->create(['order' => 1]);

        app()->setLocale('en');

        asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), ['title' => 'Pateiktas lietuviškai'])
            ->assertSessionHasNoErrors();

        expect($agendaItem->fresh()->getTranslation('title', 'lt'))->toBe('Pateiktas lietuviškai')
            ->and($agendaItem->fresh()->getTranslation('title', 'en'))
            ->not->toBe('Pateiktas lietuviškai');
    });

    test('the bulk paste box files every title under Lithuanian', function (): void {
        app()->setLocale('en');

        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Pirmas klausimas', 'Antras klausimas'],
            ])
            ->assertSessionHasNoErrors();

        expect($this->meeting->agendaItems()->get()->map->getTranslations('title')->all())
            ->toBe([
                ['lt' => 'Pirmas klausimas'],
                ['lt' => 'Antras klausimas'],
            ]);
    });

    test('a meeting description edit is persisted', function (): void {
        // Regression: UpdateMeetingRequest accepted no `description`, so MeetingForm's
        // textarea silently discarded every edit to an existing meeting.
        asUser($this->admin)
            ->patch(route('meetings.update', $this->meeting->id), [
                'start_time' => '2026-05-14 10:00:00',
                'type' => $this->meeting->type?->value,
                'description' => ['lt' => 'Naujas aprašymas', 'en' => 'New description'],
            ])
            ->assertSessionHasNoErrors();

        expect($this->meeting->fresh()->getTranslation('description', 'en'))->toBe('New description');
    });
});

describe('admin editor payload', function (): void {
    test('the editor is served every locale, not the current one', function (): void {
        $agendaItem = AgendaItem::factory()->for($this->meeting)->create([
            'title' => ['lt' => 'Lietuviškas', 'en' => 'English'],
            'order' => 1,
        ]);

        Vote::factory()->main()->for($agendaItem, 'agendaItem')->create([
            'title' => ['lt' => 'Balsavimas', 'en' => 'Vote'],
        ]);

        asUser($this->admin)
            ->get(route('agendaItems.edit', $agendaItem->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Representation/EditAgendaItem')
                ->where('agendaItem.title.lt', 'Lietuviškas')
                ->where('agendaItem.title.en', 'English')
                ->where('agendaItem.votes.0.title.en', 'Vote')
                // The navigator list is display text and stays localized.
                ->where('siblingAgendaItems.0.title', 'Lietuviškas'));
    });
});

describe('search indexing', function (): void {
    test('the index holds Lithuanian regardless of the request locale', function (): void {
        $agendaItem = AgendaItem::factory()->for($this->meeting)->create([
            'title' => ['lt' => 'Lietuviškas', 'en' => 'English'],
            'order' => 1,
        ]);

        app()->setLocale('en');

        expect($agendaItem->toSearchableArray()['title'])->toBe('Lietuviškas');
    });
});
