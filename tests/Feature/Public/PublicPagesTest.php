<?php

use App\Enums\TenantType;
use App\Mail\FeedbackMail;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\News;
use App\Models\Page;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SystemDutyTypes;
use App\Settings\DutyTypeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('home page gets default public props', function (): void {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('app', fn (Assert $page) => $page
                ->has('env')
                ->has('locale')
                ->has('path')
                ->has('url')
            )
            ->has('mainNavigation')
            ->has('tenants')
            ->has('tenant', fn (Assert $page) => $page
                ->has('alias')
                ->has('banners')
                ->has('id')
                ->has('shortname')
                ->has('type')
                ->has('subdomain')
                ->has('homeUrl')
                ->has('links')
            )
        );
});

test('no auth without authentication', function (): void {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->where('auth', null)
        );
});

test('can open the home page', function (): void {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
        );
});

test('catch-all fallback route 404s for an unrecognized host instead of crashing', function (): void {
    // The trailing {permalink} catch-all in routes/web.php carries no domain constraint, so a
    // request whose Host never maps to a tenant alias (e.g. a raw IP, as pest-plugin-browser's
    // ephemeral test server uses) must 404 cleanly rather than crash assigning null into
    // PublicController's non-nullable $tenant property. SetLocale 301s locale-less paths to
    // their /lt/… form first, so request the localized URL the redirect produces.
    $this->get('/lt/some-unmatched-path', ['HTTP_HOST' => '127.0.0.1:12345'])
        ->assertNotFound();
});

test('page route is registered as fallback and does not match admin routes', function (): void {
    $route = app('router')->getRoutes()->getByName('page');

    expect($route)->not->toBeNull()
        ->and($route->isFallback)->toBeTrue();

    $match = app('router')->getRoutes()->match(Request::create('https://www.vusa.test/mano/users'));
    expect($match->getName())->toBe('users.index');
});

test('can open news archive', function (): void {
    $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt', 'newsString' => 'naujienos']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('news', fn (Assert $page) => $page
                ->has('data')
                ->etc()
            )
        );
});

test('can open news', function (): void {
    $mainTenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $news = News::factory()->for($mainTenant)->create();

    $this->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'news' => $news->permalink, 'newsString' => 'naujiena']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
        );
});

test('news route resolves the main tenant article when permalink is shared', function (): void {
    $mainTenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $otherTenant = Tenant::factory()->create([
        'alias' => 'test-news-tenant',
    ]);

    $mainNews = News::factory()->for($mainTenant)->create([
        'permalink' => 'shared-permalink',
        'title' => 'Main tenant article',
    ]);

    News::factory()->for($otherTenant)->create([
        'permalink' => 'shared-permalink',
        'title' => 'Other tenant article',
    ]);

    $this->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'news' => 'shared-permalink', 'newsString' => 'naujiena']), ['HTTP_HOST' => 'www.vusa.test'])
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
            ->where('article.id', $mainNews->id)
            ->where('article.title', $mainNews->title)
        );
});

test('news route resolves the padalinys article when permalink is shared', function (): void {
    $mainTenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $otherTenant = Tenant::factory()->create([
        'alias' => 'test-news-tenant',
    ]);

    News::factory()->for($mainTenant)->create([
        'permalink' => 'shared-permalink',
        'title' => 'Main tenant article',
    ]);

    $otherNews = News::factory()->for($otherTenant)->create([
        'permalink' => 'shared-permalink',
        'title' => 'Other tenant article',
    ]);

    $this->get(route('news', ['subdomain' => $otherTenant->alias, 'lang' => 'lt', 'news' => 'shared-permalink', 'newsString' => 'naujiena']), ['HTTP_HOST' => $otherTenant->alias.'.vusa.test'])
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
            ->where('article.id', $otherNews->id)
            ->where('article.title', $otherNews->title)
        );
});

test('can open student representative page', function (): void {
    $this->get(route('contacts.studentRepresentatives', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowStudentReps')
            ->has('types')
        );
});

test('can open contacts search page with student rep type slugs', function (): void {
    $this->get(route('contacts', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowContacts')
            ->has('institutionTypes')
            ->has('studentRepTypeSlugs')
        );
});

test('contacts search page lists a used sub-type together with its parent', function (): void {
    $parent = InstitutionType::factory()->create(['slug' => 'tevinis-tipas']);
    $child = InstitutionType::factory()->create(['slug' => 'vaikinis-tipas', 'parent_id' => $parent->id]);
    Institution::factory()->create()->types()->attach($child);

    $this->get(route('contacts', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('institutionTypes', fn ($types) => collect($types)->contains(fn (array $type) => $type['slug'] === 'tevinis-tipas' && $type['parent_slug'] === null)
                && collect($types)->contains(fn (array $type) => $type['slug'] === 'vaikinis-tipas' && $type['parent_slug'] === 'tevinis-tipas'))
        );
});

test('can open institution page', function (): void {
    $institution = Institution::factory()->create();

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->has('contacts')
        );
});

test('can open representation padaliniai category', function (): void {
    $this->get(route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'padaliniai']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowContactCategory')
            ->has('institutions')
        );
});

test('can open student representative organ category', function (): void {
    $this->get(route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'studentu-atstovu-organas']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowStudentReps')
            ->has('types')
            ->has('categoryType')
        );
});

test('a retired category alias 301s to its new destination', function (): void {
    $this->get(route('category', ['subdomain' => 'www', 'lang' => 'lt', 'alias' => 'freshmen-camps']))
        ->assertRedirect()
        ->assertStatus(301);
});

test('an unknown category alias 404s', function (): void {
    $this->get(route('category', ['subdomain' => 'www', 'lang' => 'lt', 'alias' => 'does-not-exist']))
        ->assertNotFound();
});

test('padalinys institution page renders duty type tabs', function (): void {
    $tenant = Tenant::factory()->create(['type' => TenantType::Padalinys]);
    $institution = Institution::factory()->create(['tenant_id' => $tenant->id]);
    $type = DutyType::factory()->create(['title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators']]);
    $duty = Duty::factory()->create(['institution_id' => $institution->id]);
    $duty->types()->attach($type);
    $settings = app(DutyTypeSettings::class);
    $settings->coordinator_type_id = $type->id;
    $settings->save();
    app(SystemDutyTypes::class)->flush();

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('dutyTypeTabs', 2)
            ->where('dutyTypeTabs.0.slug', 'all')
            ->where('dutyTypeTabs.0.label', 'Visi')
            ->where('dutyTypeTabs.1.slug', 'koordinatoriai')
            ->where('activeDutyTypeTab', 'all')
        );
});

// Contact grouping tests
test('institution page with no grouping shows flat contacts', function (): void {
    $institution = Institution::factory()->create();

    // Create duties with no grouping (default)
    $duty1 = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Pirmininkas', 'en' => 'President'],
    ]);

    $duty2 = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Vicepirmininkas', 'en' => 'Vice President'],
    ]);

    // Create users for duties
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $duty1->users()->attach($user1, ['start_date' => now()]);
    $duty2->users()->attach($user2, ['start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->has('contacts', 2) // Should have 2 contacts flattened
            ->missing('contactSections')
        );
});

test('a tenant-ordered duty shares one grid with the other duties and names the tenant on each card', function (): void {
    $owningTenant = Tenant::where('shortname', 'VU SA')->firstOrFail();
    $institution = Institution::factory()->create(['tenant_id' => $owningTenant->id]);

    $chair = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Parlamento pirmininkas', 'en' => 'Chair of Parliament'],
        'order' => 0,
    ]);
    $member = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'tenant',
        'name' => ['lt' => 'Parlamento narys', 'en' => 'Member of Parliament'],
        'order' => 1,
    ]);

    $mif = Tenant::factory()->create(['shortname' => 'VU MIF']);
    $chgf = Tenant::factory()->create(['shortname' => 'VU CHGF']);

    $chairUser = User::factory()->create();
    $mifUser = User::factory()->create();
    $chgfUser = User::factory()->create();
    $ownUser = User::factory()->create();

    $chair->users()->attach($chairUser, ['start_date' => now()]);
    $member->users()->attach($mifUser, ['tenant_id' => $mif->id, 'start_date' => now()]);
    $member->users()->attach($ownUser, ['tenant_id' => null, 'start_date' => now()]);
    $member->users()->attach($chgfUser, ['tenant_id' => $chgf->id, 'start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->missing('contactSections')
            ->has('contacts', 4)
            ->where('contacts.0.id', $chairUser->id)
            ->where('contacts.0.duties.0.contact_tenant', null)
            ->where('contacts.1.id', $chgfUser->id)
            ->where('contacts.1.duties.0.name', 'Parlamento narys')
            ->where('contacts.1.duties.0.contact_tenant', 'VU CHGF')
            ->where('contacts.2.duties.0.contact_tenant', 'VU MIF')
            ->where('contacts.3.id', $ownUser->id)
            ->where('contacts.3.duties.0.contact_tenant', $owningTenant->shortname)
        );
});

test('a study-program-ordered duty sorts by programme and puts it on the card', function (): void {
    $institution = Institution::factory()->create();
    $tenant = Tenant::query()->first();

    $mathematics = StudyProgram::factory()->create(['name' => 'Mathematics', 'tenant_id' => $tenant->id]);
    $informatics = StudyProgram::factory()->create(['name' => 'Computer Science', 'tenant_id' => $tenant->id]);

    $duty = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'study_program',
        'name' => ['lt' => 'Kuratorius', 'en' => 'Mentor'],
    ]);

    $withoutProgram = User::factory()->create();
    $mathematician = User::factory()->create();
    $informatician = User::factory()->create();

    $duty->users()->attach($withoutProgram, ['start_date' => now()]);
    $duty->users()->attach($mathematician, ['study_program_id' => $mathematics->id, 'start_date' => now()]);
    $duty->users()->attach($informatician, ['study_program_id' => $informatics->id, 'start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts', 3)
            ->where('contacts.0.id', $informatician->id)
            ->where('contacts.0.duties.0.pivot.study_program.name', 'Computer Science')
            ->where('contacts.0.duties.0.contact_tenant', null)
            ->where('contacts.1.id', $mathematician->id)
            ->where('contacts.2.id', $withoutProgram->id)
            ->where('contacts.2.duties.0.pivot.study_program', null)
        );
});

test('contacts follow duty order and a person with two duties gets one card', function (): void {
    $institution = Institution::factory()->create();

    $second = Duty::factory()->create(['institution_id' => $institution->id, 'name' => ['lt' => 'Antras', 'en' => 'Second'], 'order' => 2]);
    $first = Duty::factory()->create(['institution_id' => $institution->id, 'name' => ['lt' => 'Pirmas', 'en' => 'First'], 'order' => 1]);

    $both = User::factory()->create();
    $onlySecond = User::factory()->create();

    $second->users()->attach($onlySecond, ['start_date' => now()]);
    $second->users()->attach($both, ['start_date' => now()]);
    $first->users()->attach($both, ['start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts', 2)
            ->where('contacts.0.id', $both->id)
            ->has('contacts.0.duties', 2)
            ->where('contacts.0.duties.0.name', 'Pirmas')
            ->where('contacts.0.duties.1.name', 'Antras')
            ->where('contacts.1.id', $onlySecond->id)
        );
});

test('a padalinys lists coordinators before curators regardless of duty order', function (): void {
    $tenant = Tenant::factory()->create(['type' => TenantType::Padalinys]);
    $institution = Institution::factory()->create(['tenant_id' => $tenant->id]);

    $coordinators = DutyType::factory()->create(['title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators']]);
    $curators = DutyType::factory()->create(['title' => ['lt' => 'Kuratoriai', 'en' => 'Mentors']]);
    $settings = app(DutyTypeSettings::class);
    $settings->coordinator_type_id = $coordinators->id;
    $settings->curator_type_id = $curators->id;
    $settings->save();
    app(SystemDutyTypes::class)->flush();

    $curatorDuty = Duty::factory()->create(['institution_id' => $institution->id, 'order' => 0]);
    $curatorDuty->types()->attach($curators);
    $otherDuty = Duty::factory()->create(['institution_id' => $institution->id, 'order' => 1]);
    $coordinatorDuty = Duty::factory()->create(['institution_id' => $institution->id, 'order' => 5]);
    $coordinatorDuty->types()->attach($coordinators);

    $curator = User::factory()->create();
    $other = User::factory()->create();
    $coordinator = User::factory()->create();

    $curatorDuty->users()->attach($curator, ['start_date' => now()]);
    $otherDuty->users()->attach($other, ['start_date' => now()]);
    $coordinatorDuty->users()->attach($coordinator, ['start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts', 3)
            ->where('contacts.0.id', $coordinator->id)
            ->where('contacts.1.id', $curator->id)
            ->where('contacts.2.id', $other->id)
        );
});

test('can leave feedback', function (): void {

    Mail::fake();

    // assert that route exists
    $response = $this->post(route('feedback.send'), [
        'feedback' => 'Test feedback',
        'href' => 'https://vusa.lt',
        'selectedText' => 'Test selected text',
    ]);

    $response->assertRedirect();

    // assert that mail was sent
    Mail::assertQueued(FeedbackMail::class);
});

test('feedback requires a non-null message', function (): void {
    Mail::fake();

    $response = $this->post(route('feedback.send'), [
        'feedback' => null,
        'href' => 'https://vusa.lt',
        'selectedText' => null,
    ]);

    $response->assertSessionHasErrors('feedback');
    Mail::assertNotQueued(FeedbackMail::class);
});

test('duty type contacts page shows a study-program-ordered duty as one grid', function (): void {
    // Find or create the central institution that's used by duty type contacts
    $institution = Institution::where('alias', 'centrinis-biuras')->first();
    if (! $institution) {
        $institution = Institution::factory()->create(['alias' => 'centrinis-biuras']);
    }

    $tenant = Tenant::factory()->create();

    // Create a type for the duty
    $type = DutyType::factory()->create([
        'title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators'],
    ]);
    $settings = app(DutyTypeSettings::class);
    $settings->coordinator_type_id = $type->id;
    $settings->save();
    app(SystemDutyTypes::class)->flush();

    // Create study programs
    $studyProgram1 = StudyProgram::factory()->create([
        'name' => 'Computer Science',
        'tenant_id' => $tenant->id,
    ]);

    $studyProgram2 = StudyProgram::factory()->create([
        'name' => 'Mathematics',
        'tenant_id' => $tenant->id,
    ]);

    // Create duty with study program grouping and attach it to our type
    $duty = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'study_program',
        'name' => ['lt' => 'Fakulteto koordinatoriai', 'en' => 'Faculty Coordinators'],
    ]);

    // Attach the type to the duty
    $duty->types()->attach($type);

    // Create users
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // Attach users to duty with study programs
    $duty->users()->attach($user1, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user2, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user3, ['study_program_id' => $studyProgram2->id, 'start_date' => now()]);

    $this->get(route('contacts.dutyType', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'koordinatoriai']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->has('contacts', 3)
            ->where('contacts.0.duties.0.pivot.study_program.name', 'Computer Science')
            ->missing('contactSections')
        );
});

test('duty type contacts page handles both grouped and flat duties correctly', function (): void {
    // Find or create the central institution that's used by duty type contacts
    $institution = Institution::where('alias', 'centrinis-biuras')->first();
    if (! $institution) {
        $institution = Institution::factory()->create(['alias' => 'centrinis-biuras']);
    }

    // Create a type for the duty
    $type = DutyType::factory()->create([
        'title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators'],
    ]);
    $settings = app(DutyTypeSettings::class);
    $settings->coordinator_type_id = $type->id;
    $settings->save();
    app(SystemDutyTypes::class)->flush();

    // Create a flat duty (no grouping)
    $flatDuty = Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Bendri koordinatoriai', 'en' => 'General Coordinators'],
    ]);
    $flatDuty->types()->attach($type);

    // Create user for flat duty
    $flatUser = User::factory()->create();
    $flatDuty->users()->attach($flatUser, ['start_date' => now()]);

    // Verify the page works and shows mixed grouping when there's at least one grouped duty
    $this->get(route('contacts.dutyType', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'koordinatoriai']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->has('contacts', 1)
        );
});
