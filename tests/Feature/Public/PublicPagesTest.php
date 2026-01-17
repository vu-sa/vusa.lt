<?php

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('home page gets default public props', function () {
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
                ->has('links')
            )
        );
});

test('no auth without authentication', function () {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->where('auth', null)
        );
});

test('can open the home page', function () {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
        );
});

test('can open news archive', function () {
    $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt', 'newsString' => 'naujienos']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('news', fn (Assert $page) => $page
                ->has('data')
                ->etc()
            )
        );
});

test('can open news', function () {
    $news = \App\Models\News::query()->inRandomOrder()->first();

    $this->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'news' => $news->permalink, 'newsString' => 'naujiena']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
        );
});

test('can open student representative page', function () {
    $this->get(route('contacts.studentRepresentatives', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowStudentReps')
            ->has('types')
        );
});

test('can open institution page', function () {
    $institution = Institution::query()->inRandomOrder()->first();

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->has('contacts')
        );
});

test('can open representation padaliniai category', function () {
    $this->get(route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'padaliniai']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowContactCategory')
            ->has('institutions')
        );
});

test('can open student representative organ category', function () {
    $this->get(route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'studentu-atstovu-organas']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowStudentReps')
            ->has('types')
            ->has('categoryType')
        );
});

// Contact grouping tests
test('institution page with no grouping shows flat contacts', function () {
    $institution = Institution::factory()->create();

    // Create duties with no grouping (default)
    $duty1 = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Pirmininkas', 'en' => 'President'],
    ]);

    $duty2 = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Vicepirmininkas', 'en' => 'Vice President'],
    ]);

    // Create users for duties
    $user1 = \App\Models\User::factory()->create();
    $user2 = \App\Models\User::factory()->create();

    $duty1->users()->attach($user1, ['start_date' => now()]);
    $duty2->users()->attach($user2, ['start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->has('contacts', 2) // Should have 2 contacts flattened
            ->missing('contactSections') // No sections for flat display
            ->missing('hasMixedGrouping') // Not using mixed grouping
        );
});

test('institution page with study program grouping shows grouped sections', function () {
    $institution = Institution::factory()->create();
    $tenant = \App\Models\Tenant::factory()->create();

    // Create study programs
    $studyProgram1 = \App\Models\StudyProgram::factory()->create([
        'name' => 'Computer Science',
        'tenant_id' => $tenant->id,
    ]);

    $studyProgram2 = \App\Models\StudyProgram::factory()->create([
        'name' => 'Mathematics',
        'tenant_id' => $tenant->id,
    ]);

    // Create duty with study program grouping
    $duty = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'study_program',
        'name' => ['lt' => 'Studentų atstovai', 'en' => 'Student Representatives'],
    ]);

    // Create users
    $user1 = \App\Models\User::factory()->create();
    $user2 = \App\Models\User::factory()->create();
    $user3 = \App\Models\User::factory()->create();

    // Attach users to duty with study programs
    $duty->users()->attach($user1, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user2, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user3, ['study_program_id' => $studyProgram2->id, 'start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->missing('contacts') // No flat contacts
            ->has('contactSections', 1) // One duty section
            ->where('hasMixedGrouping', true)
            ->has('contactSections.0', fn (Assert $page) => $page
                ->where('type', 'grouped_duty')
                ->where('dutyName', 'Studentų atstovai') // Should get the Lithuanian translation
                ->has('groups', 2) // Two study program groups
                ->has('groups.0.contacts', 2) // First group has 2 contacts
                ->has('groups.1.contacts', 1) // Second group has 1 contact
            )
        );
});

test('institution page with tenant grouping shows grouped sections', function () {
    $institution = Institution::factory()->create();

    // Create tenants
    $tenant1 = \App\Models\Tenant::factory()->create(['shortname' => 'VU CHGF']);
    $tenant2 = \App\Models\Tenant::factory()->create(['shortname' => 'VU MIF']);

    // Create study programs under different tenants
    $studyProgram1 = \App\Models\StudyProgram::factory()->create([
        'name' => 'History',
        'tenant_id' => $tenant1->id,
    ]);

    $studyProgram2 = \App\Models\StudyProgram::factory()->create([
        'name' => 'Computer Science',
        'tenant_id' => $tenant2->id,
    ]);

    // Create duty with tenant grouping
    $duty = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'tenant',
        'name' => ['lt' => 'Dekano atstovai', 'en' => 'Dean Representatives'],
    ]);

    // Create users
    $user1 = \App\Models\User::factory()->create();
    $user2 = \App\Models\User::factory()->create();

    // Attach users to duty with study programs from different tenants
    $duty->users()->attach($user1, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user2, ['study_program_id' => $studyProgram2->id, 'start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->missing('contacts')
            ->has('contactSections', 1)
            ->where('hasMixedGrouping', true)
            ->has('contactSections.0', fn (Assert $page) => $page
                ->where('type', 'grouped_duty')
                ->where('dutyName', 'Dekano atstovai')
                ->has('groups', 2) // Two tenant groups
                ->has('groups.0.contacts', 1)
                ->has('groups.1.contacts', 1)
            )
        );
});

test('institution page with mixed grouping shows both flat and grouped duties', function () {
    $institution = Institution::factory()->create();
    $tenant = \App\Models\Tenant::factory()->create();

    $studyProgram = \App\Models\StudyProgram::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    // Create flat duty (no grouping)
    $flatDuty = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Pirmininkas', 'en' => 'President'],
        'order' => 1,
    ]);

    // Create grouped duty
    $groupedDuty = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'study_program',
        'name' => ['lt' => 'Studentų atstovai', 'en' => 'Student Representatives'],
        'order' => 2,
    ]);

    // Create users
    $user1 = \App\Models\User::factory()->create();
    $user2 = \App\Models\User::factory()->create();
    $user3 = \App\Models\User::factory()->create();

    // Attach users
    $flatDuty->users()->attach($user1, ['start_date' => now()]);
    $groupedDuty->users()->attach($user2, ['study_program_id' => $studyProgram->id, 'start_date' => now()]);
    $groupedDuty->users()->attach($user3, ['study_program_id' => $studyProgram->id, 'start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->missing('contacts')
            ->has('contactSections', 2) // Two duty sections
            ->where('hasMixedGrouping', true)
            ->has('contactSections.0', fn (Assert $page) => $page
                ->where('type', 'flat_duty')
                ->where('dutyName', 'Pirmininkas')
                ->has('contacts', 1)
                ->missing('groups')
            )
            ->has('contactSections.1', fn (Assert $page) => $page
                ->where('type', 'grouped_duty')
                ->where('dutyName', 'Studentų atstovai')
                ->has('groups', 1) // One study program group
                ->has('groups.0.contacts', 2)
                ->missing('contacts')
            )
        );
});

test('duty grouping respects duty order within institution', function () {
    $institution = Institution::factory()->create();
    $tenant = \App\Models\Tenant::factory()->create();

    $studyProgram = \App\Models\StudyProgram::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    // Create duties with specific order - mix of grouped and flat to trigger contactSections
    $duty3 = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Trečias', 'en' => 'Third'],
        'order' => 3,
    ]);

    $duty1 = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'study_program', // Make this one grouped
        'name' => ['lt' => 'Pirmas', 'en' => 'First'],
        'order' => 1,
    ]);

    $duty2 = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Antras', 'en' => 'Second'],
        'order' => 2,
    ]);

    // Create users for duties
    $duty1->users()->attach(\App\Models\User::factory()->create(), ['study_program_id' => $studyProgram->id, 'start_date' => now()]);
    $duty2->users()->attach(\App\Models\User::factory()->create(), ['start_date' => now()]);
    $duty3->users()->attach(\App\Models\User::factory()->create(), ['start_date' => now()]);

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('contactSections', 3)
            ->where('hasMixedGrouping', true)
            ->where('contactSections.0.dutyName', 'Pirmas') // First by order
            ->where('contactSections.1.dutyName', 'Antras') // Second by order
            ->where('contactSections.2.dutyName', 'Trečias') // Third by order
        );
});

test('can leave feedback', function () {

    Mail::fake();

    // assert that route exists
    $response = $this->post(route('feedback.send'), [
        'feedback' => 'Test feedback',
        'href' => 'https://vusa.lt',
        'selectedText' => 'Test selected text',
    ]);

    $response->assertRedirect();

    // assert that mail was sent
    Mail::assertQueued(\App\Mail\FeedbackMail::class);
});

test('duty type contacts page with grouping shows grouped sections', function () {
    // Find or create the central institution that's used by duty type contacts
    $institution = Institution::where('alias', 'centrinis-biuras')->first();
    if (! $institution) {
        $institution = Institution::factory()->create(['alias' => 'centrinis-biuras']);
    }

    $tenant = \App\Models\Tenant::factory()->create();

    // Create or find a type for the duty (e.g., koordinatoriai)
    $type = \App\Models\Type::where('slug', 'koordinatoriai')->first();
    if (! $type) {
        $type = \App\Models\Type::factory()->create([
            'slug' => 'koordinatoriai',
            'title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators'],
            'model_type' => 'App\\Models\\Duty',
        ]);
    }

    // Create study programs
    $studyProgram1 = \App\Models\StudyProgram::factory()->create([
        'name' => 'Computer Science',
        'tenant_id' => $tenant->id,
    ]);

    $studyProgram2 = \App\Models\StudyProgram::factory()->create([
        'name' => 'Mathematics',
        'tenant_id' => $tenant->id,
    ]);

    // Create duty with study program grouping and attach it to our type
    $duty = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'study_program',
        'name' => ['lt' => 'Fakulteto koordinatoriai', 'en' => 'Faculty Coordinators'],
    ]);

    // Attach the type to the duty
    $duty->types()->attach($type);

    // Create users
    $user1 = \App\Models\User::factory()->create();
    $user2 = \App\Models\User::factory()->create();
    $user3 = \App\Models\User::factory()->create();

    // Attach users to duty with study programs
    $duty->users()->attach($user1, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user2, ['study_program_id' => $studyProgram1->id, 'start_date' => now()]);
    $duty->users()->attach($user3, ['study_program_id' => $studyProgram2->id, 'start_date' => now()]);

    $this->get(route('contacts.dutyType', ['subdomain' => 'www', 'lang' => 'lt', 'type' => $type->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            ->missing('contacts') // No flat contacts
            ->has('contactSections') // Has contact sections
            ->where('hasMixedGrouping', true)
        );
});

test('duty type contacts page handles both grouped and flat duties correctly', function () {
    // Find or create the central institution that's used by duty type contacts
    $institution = Institution::where('alias', 'centrinis-biuras')->first();
    if (! $institution) {
        $institution = Institution::factory()->create(['alias' => 'centrinis-biuras']);
    }

    // Create or find a type for the duty (e.g., koordinatoriai)
    $type = \App\Models\Type::where('slug', 'koordinatoriai')->first();
    if (! $type) {
        $type = \App\Models\Type::factory()->create([
            'slug' => 'koordinatoriai',
            'title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators'],
            'model_type' => 'App\\Models\\Duty',
        ]);
    }

    // Create a flat duty (no grouping)
    $flatDuty = \App\Models\Duty::factory()->create([
        'institution_id' => $institution->id,
        'contacts_grouping' => 'none',
        'name' => ['lt' => 'Bendri koordinatoriai', 'en' => 'General Coordinators'],
    ]);
    $flatDuty->types()->attach($type);

    // Create user for flat duty
    $flatUser = \App\Models\User::factory()->create();
    $flatDuty->users()->attach($flatUser, ['start_date' => now()]);

    // Verify the page works and shows mixed grouping when there's at least one grouped duty
    $this->get(route('contacts.dutyType', ['subdomain' => 'www', 'lang' => 'lt', 'type' => $type->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowInstitution')
            ->has('institution')
            // Should have either contacts (flat mode) or contactSections (mixed mode)
            ->etc()
        );
});
