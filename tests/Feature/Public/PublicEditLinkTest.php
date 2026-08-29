<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Calendar events geocode their location — never let that reach the network
    Http::fake();

    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $this->editor = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->plainUser = makeUser($this->tenant);
});

function editLinkPageUrl(Page $page, Tenant $tenant): string
{
    return route('page', [
        'subdomain' => $tenant->alias === 'vusa' ? 'www' : $tenant->alias,
        'lang' => 'lt',
        'permalink' => $page->permalink,
    ]);
}

function editLinkCalendarEventUrl(Calendar $calendar): string
{
    return route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $calendar->date->format('Y'),
        'month' => $calendar->date->format('m'),
        'day' => $calendar->date->format('d'),
        'slug' => Str::slug($calendar->title),
    ]);
}

function editLinkInstitutionUrl(Institution $institution): string
{
    return route('contacts.institution', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'contactsString' => 'kontaktai',
        'institution' => $institution->id,
    ]);
}

function editLinkMeetingUrl(Meeting $meeting): string
{
    return route('publicMeetings.show', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'meetingsString' => 'posedziai',
        'meeting' => $meeting->id,
    ]);
}

describe('content pages', function (): void {
    test('guests get no edit link', function (): void {
        $page = Page::factory()->for($this->tenant)->create([
            'permalink' => 'edit-link-test-page',
            'lang' => 'lt',
            'is_active' => true,
        ]);

        $this->get(editLinkPageUrl($page, $this->tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('an authorized editor of the owning tenant gets the edit link', function (): void {
        $page = Page::factory()->for($this->tenant)->create([
            'permalink' => 'edit-link-test-page',
            'lang' => 'lt',
            'is_active' => true,
        ]);

        asUser($this->editor)
            ->get(editLinkPageUrl($page, $this->tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('pages.edit', $page))
                ->where('publicEditLink.type', 'page')
                ->where('publicEditLink.id', $page->id));
    });

    test('an authenticated user without the permission gets no edit link', function (): void {
        $page = Page::factory()->for($this->tenant)->create([
            'permalink' => 'edit-link-test-page',
            'lang' => 'lt',
            'is_active' => true,
        ]);

        asUser($this->plainUser)
            ->get(editLinkPageUrl($page, $this->tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('a padalinys editor does not get an edit link for another tenant page', function (): void {
        $otherTenant = Tenant::factory()->create(['alias' => 'edit-link-other']);
        $otherEditor = makeTenantUserWithRole('Communication Coordinator', $otherTenant);

        $page = Page::factory()->for($otherTenant)->create([
            'permalink' => 'edit-link-other-page',
            'lang' => 'lt',
            'is_active' => true,
        ]);

        asUser($this->editor)
            ->get(editLinkPageUrl($page, $otherTenant))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));

        asUser($otherEditor)
            ->get(editLinkPageUrl($page, $otherTenant))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('pages.edit', $page))
                ->where('publicEditLink.id', $page->id));
    });
});

describe('news articles', function (): void {
    test('guests get no edit link', function (): void {
        $news = News::factory()->for($this->tenant)->create(['permalink' => 'edit-link-news']);

        $this->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'newsString' => 'naujiena', 'news' => $news->permalink]))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('an authorized editor gets the edit link', function (): void {
        $news = News::factory()->for($this->tenant)->create(['permalink' => 'edit-link-news']);

        asUser($this->editor)
            ->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'newsString' => 'naujiena', 'news' => $news->permalink]))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('news.edit', $news))
                ->where('publicEditLink.type', 'news')
                ->where('publicEditLink.id', $news->id));
    });
});

describe('calendar events', function (): void {
    test('guests get no edit link', function (): void {
        $event = Calendar::factory()->for($this->tenant)->create(['is_draft' => false]);

        $this->get(editLinkCalendarEventUrl($event))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('an authorized editor gets the edit link', function (): void {
        $event = Calendar::factory()->for($this->tenant)->create(['is_draft' => false]);

        asUser($this->editor)
            ->get(editLinkCalendarEventUrl($event))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('calendar.edit', $event))
                ->where('publicEditLink.type', 'calendar')
                ->where('publicEditLink.id', $event->id));
    });

    test('a padalinys editor does not get an edit link for another tenant event', function (): void {
        $otherTenant = Tenant::factory()->create(['alias' => 'edit-link-other']);
        $otherEditor = makeTenantUserWithRole('Communication Coordinator', $otherTenant);

        $event = Calendar::factory()->for($otherTenant)->create(['is_draft' => false]);

        asUser($this->editor)
            ->get(editLinkCalendarEventUrl($event))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));

        asUser($otherEditor)
            ->get(editLinkCalendarEventUrl($event))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('calendar.edit', $event))
                ->where('publicEditLink.id', $event->id));
    });
});

describe('homepage', function (): void {
    test('guests get no edit link', function (): void {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('an editor who may update the main page gets the edit link', function (): void {
        asUser($this->editor)
            ->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('tenants.editMainPage', $this->tenant))
                ->where('publicEditLink.type', 'homepage')
                ->where('publicEditLink.id', $this->tenant->id));
    });
});

describe('institution contact pages', function (): void {
    test('guests get no edit link', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();

        $this->get(editLinkInstitutionUrl($institution))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('an authorized editor gets the edit link', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();

        asUser($this->editor)
            ->get(editLinkInstitutionUrl($institution))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('institutions.edit', $institution))
                ->where('publicEditLink.type', 'institution')
                ->where('publicEditLink.id', $institution->id));
    });
});

describe('meeting pages', function (): void {
    beforeEach(function (): void {
        $type = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create();
        $this->institution = Institution::factory()->for($this->tenant)->create();
        $this->institution->types()->attach($type);

        $this->meeting = Meeting::factory()->create(['start_time' => now()->addDays(3)]);
        $this->meeting->institutions()->attach($this->institution);

        AgendaItem::factory()->for($this->meeting)->create([
            'order' => 1,
            'type' => AgendaItemType::Voting->value,
            'start_time' => '18:30',
        ]);

        // The meeting page is settings-only — see Meeting::isPubliclyVisible(). A published
        // calendar event no longer makes it public by itself.
        app(MeetingSettings::class)->fill([
            'public_meeting_institution_type_ids' => [$type->id],
        ])->save();
    });

    test('guests get no edit link', function (): void {
        $this->get(editLinkMeetingUrl($this->meeting))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props->where('publicEditLink', null));
    });

    test('an authorized editor gets the edit link', function (): void {
        asUser($this->editor)
            ->get(editLinkMeetingUrl($this->meeting))
            ->assertOk()
            ->assertInertia(fn (Assert $props) => $props
                ->where('publicEditLink.url', route('meetings.edit', $this->meeting))
                ->where('publicEditLink.type', 'meeting')
                ->where('publicEditLink.id', $this->meeting->id));
    });
});
