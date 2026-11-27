<?php

use App\Models\Cadence;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionLink;
use App\Models\InstitutionSecretary;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\StudyProgram;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Institutions\InstitutionPresenter;
use App\Settings\MeetingSettings;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
});

describe('reorderDuties', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    test('persists the new order for each duty in a single batch', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $dutyA = Duty::factory()->create(['institution_id' => $institution->id, 'order' => 1]);
        $dutyB = Duty::factory()->create(['institution_id' => $institution->id, 'order' => 2]);

        asUser($this->admin)->post(route('institutions.reorderDuties'), [
            'duties' => [
                ['id' => $dutyA->id, 'order' => 5],
                ['id' => $dutyB->id, 'order' => 3],
            ],
        ])->assertRedirect();

        expect($dutyA->fresh()->order)->toBe(5)
            ->and($dutyB->fresh()->order)->toBe(3);
    });

    test('a user without institution update permission cannot reorder duties', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $duty = Duty::factory()->create(['institution_id' => $institution->id, 'order' => 1]);

        $plainUser = makeUser($this->tenant);

        asUser($plainUser)->post(route('institutions.reorderDuties'), [
            'duties' => [['id' => $duty->id, 'order' => 9]],
        ])->assertStatus(403);

        expect($duty->fresh()->order)->toBe(1);
    });

    test('cannot reorder duties of an institution in another tenant', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherDuty = Duty::factory()->create(['institution_id' => $otherInstitution->id, 'order' => 1]);

        asUser($this->admin)->post(route('institutions.reorderDuties'), [
            'duties' => [['id' => $otherDuty->id, 'order' => 9]],
        ])->assertStatus(403);

        expect($otherDuty->fresh()->order)->toBe(1);
    });

    test('leaves every order unchanged when a duty belongs to a trashed institution in another tenant', function (): void {
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create(['order' => 1]);
        $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
        $trashedInstitution = Institution::factory()->for($otherTenant)->create();
        $foreignDuty = Duty::factory()->for($trashedInstitution)->create(['order' => 2]);
        $trashedInstitution->delete();

        asUser($this->admin)->post(route('institutions.reorderDuties'), [
            'duties' => [
                ['id' => $duty->id, 'order' => 5],
                ['id' => $foreignDuty->id, 'order' => 6],
            ],
        ])->assertForbidden();

        expect($duty->fresh()->order)->toBe(1)
            ->and($foreignDuty->fresh()->order)->toBe(2);
    });
});

describe('unauthorized access', function (): void {
    beforeEach(function (): void {
        $this->user = makeUser($this->tenant);
    });

    test('browses institutions — active ones are public — starting on their padalinys', function (): void {
        asUserWithInertia($this->user)->get(route('institutions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexInstitution')
                ->where('defaultTenantShortnames', [$this->tenant->shortname])
            );
    });

    test('reads an active institution outside their reach as its public face only', function (): void {
        $institution = Institution::factory()->for(Tenant::factory())->create(['is_active' => 1]);
        $institution->tasks()->create(['name' => 'Internal task']);
        $duty = Duty::factory()->for($institution)->create();
        $member = User::factory()->create(['email' => 'private@example.test', 'phone' => '+37060000000']);
        $duty->users()->attach($member, ['start_date' => today()->subDay(), 'additional_email' => 'term@example.test']);

        asUserWithInertia($this->user)->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowInstitution')
                ->where('readOnly', true)
                ->where('can.update', false)
                ->where('can.recordMeeting', false)
                ->where('can.reportActivity', false)
                ->missing('files')
                ->missing('tasks')
                ->missing('relatedInstitutions')
                ->where('institution.secretaries', [])
                ->where('institution.sharepointPath', null)
                ->missing('overview.recentComments')
                ->whereType('institution.id', 'string')
                ->whereType('institution.is_active', 'boolean')
                ->missing('institution.meeting_periodicity_days')
                ->whereType('overview.current_users', 'array')
                ->where('overview.current_users.0.id', $member->id)
                ->missing('overview.current_users.0.email')
                ->missing('overview.current_users.0.phone')
                ->missing('overview.current_users.0.pivot.additional_email')
                ->has('overviewDuties', 1)
                ->missing('overviewDuties.0.current_users.0.email')
                ->where('canViewActivityRequests', false)
                ->missing('activityRequests')
                ->reload(only: ['activityRequests'], callback: fn (Assert $history) => $history->missing('activityRequests'))
                ->reloadOnly(['files', 'tasks', 'relatedInstitutions', 'institutionLinks', 'assignmentTerms'], fn (Assert $hidden) => $hidden
                    ->where('files', [])->where('tasks', [])
                    ->where('relatedInstitutions', [])->where('institutionLinks', [])->where('assignmentTerms', []))
                ->reloadOnly(['duties', 'problems', 'management'], fn (Assert $panels) => $panels
                    ->whereType('duties', 'array')
                    ->whereType('problems', 'array')
                    ->where('management', null))
            );
    });

    test('says the meetings exist but are hidden when they are not public', function (): void {
        $institution = Institution::factory()->for(Tenant::factory())->create(['is_active' => 1]);
        Meeting::factory()->hasAttached($institution)->create();

        asUserWithInertia($this->user)->get(route('institutions.show', $institution))
            ->assertInertia(fn ($page) => $page
                ->where('overview.meetings_hidden', true)
                ->where('overview.recentMeetings', [])
                ->where('overview.activity_status', null)
                ->missing('meetings')
                // Nothing to hear about, so nothing to follow
                ->where('subscription', null)
            );
    });

    test('the public face keeps public meeting summaries typed in both loads', function (): void {
        $institution = Institution::factory()->for(Tenant::factory())->create(['is_active' => 1]);
        $type = InstitutionType::factory()->create();
        $institution->types()->attach($type);
        $settings = app(MeetingSettings::class);
        $settings->setPublicMeetingInstitutionTypeIds([$type->id]);
        $settings->save();
        $meeting = Meeting::factory()->hasAttached($institution)->create(['type' => null]);

        asUserWithInertia($this->user)->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/ShowInstitution')
                ->where('readOnly', true)
                ->where('institution.has_public_meetings', true)
                ->where('overview.recentMeetings.0.id', $meeting->id)
                ->whereType('overview.activity_status.status', 'string')
                ->missing('files')
                ->missing('tasks')
                ->where('overview.recentMeetings.0.type', null)
                ->missing('overview.recentMeetings.0.agenda_item_titles')
                ->whereType('overview.recentMeetings.0.has_protocol', 'boolean')
                // The meetings tab searches the index with the reader's own scoped key.
                ->missing('meetings')
                ->reloadOnly('management', fn (Assert $panels) => $panels->where('management', null)));
    });

    test('cannot open an inactive institution outside their reach', function (): void {
        $institution = Institution::factory()->for(Tenant::factory())->create(['is_active' => 0]);

        asUser($this->user)->get(route('institutions.show', $institution))->assertForbidden();
    });

    test('cannot access institution create page', function (): void {
        asUser($this->user)->get(route('institutions.create'))->assertStatus(403);
    });

    test('cannot store institution', function (): void {
        asUser($this->user)->post(route('institutions.store'), [
            'name' => ['lt' => 'Test Institution'],
            'short_name' => ['lt' => 'test'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test-alias',
        ])->assertStatus(403);
    });

    test('cannot access the institution edit page', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->user)->get(route('institutions.edit', $institution))->assertStatus(403);
    });

    test('cannot update institution', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->user)->put(route('institutions.update', $institution), [
            'name' => ['lt' => 'Updated Institution'],
            'short_name' => ['lt' => 'updated'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'updated-alias',
        ])->assertStatus(403);
    });

    test('cannot delete institution', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->user)->delete(route('institutions.destroy', $institution))->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    test('each optional tab returns only the props explicitly requested', function (array $only): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        InstitutionActivityRequest::factory()->create(['institution_id' => $institution->id, 'recipient_id' => $this->admin->id]);
        Duty::factory()->for($institution)->count(InstitutionPresenter::OVERVIEW_DUTY_LIMIT + 1)->create();
        $optional = ['duties', 'tasks', 'problems', 'management', 'relatedInstitutions', 'institutionLinks', 'files', 'activityRequests', 'assignmentTerms'];

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('overview')
                ->missingAll($optional)
                ->reloadOnly($only, fn (Assert $tab) => $tab
                    ->missing('overview')
                    ->missingAll(array_values(array_diff($optional, $only)))));
    })->with([
        'duties' => [['duties']],
        'tasks' => [['tasks']],
        'problems' => [['problems']],
        'terms' => [['management']],
        'relations' => [['relatedInstitutions', 'institutionLinks']],
        'files' => [['files']],
        'activity requests' => [['activityRequests']],
    ]);

    test('the overview preview stays bounded while full duties wait for their tab', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        Duty::factory()->for($institution)->count(InstitutionPresenter::OVERVIEW_DUTY_LIMIT)->create();

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page->missing('duties')->has('overviewDuties', InstitutionPresenter::OVERVIEW_DUTY_LIMIT));

        Duty::factory()->for($institution)->create();

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('duties')
                ->reloadOnly('duties', fn (Assert $tab) => $tab->has('duties', InstitutionPresenter::OVERVIEW_DUTY_LIMIT + 1)));
    });

    test('separates the nearest upcoming meeting from bounded past meetings', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        foreach ([-4, -3, -2, -1, 1, 9] as $days) {
            $meeting = Meeting::factory()->create(['start_time' => now()->addDays($days)]);
            $meeting->institutions()->attach($institution);
            if ($days === 1) {
                $upcomingId = $meeting->id;
            }
        }
        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page
                ->where('upcomingMeeting.id', $upcomingId)
                ->has('overview.recentMeetings', 3));
    });

    test('says whether there are files, so an empty Failai tab can give way to an add action', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page->where('institution.has_files', false));

        FileableFile::factory()->create(['fileable_type' => MorphMap::alias(Institution::class), 'fileable_id' => $institution->id]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page->where('institution.has_files', true));
    });

    test('the task badge counts incomplete institution and meeting tasks without loading the tab', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $meeting = Meeting::factory()->hasAttached($institution)->create();
        $institution->tasks()->create(['name' => 'Institution task']);
        $meeting->tasks()->create(['name' => 'Meeting task']);
        $institution->tasks()->create(['name' => 'Completed', 'completed_at' => now()]);
        // The factory attaches to a random existing institution, which may be this one.
        Task::factory()->create(['taskable_id' => Institution::factory()->create()->id]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page->where('incompleteTasksCount', 2)->missing('tasks'));
    });

    test('meeting summaries keep outcomes and available-file badges with the compact query', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $meeting = Meeting::factory()->hasAttached($institution)->create();
        $item = $meeting->agendaItems()->create(['type' => 'voting', 'title' => ['lt' => 'Klausimas', 'en' => 'Question'], 'order' => 1]);
        $item->votes()->create(['is_main' => true, 'is_consensus' => false, 'decision' => 'positive', 'student_vote' => 'positive', 'student_benefit' => 'positive']);
        FileableFile::factory()->protocol()->create(['fileable_type' => MorphMap::alias(Meeting::class), 'fileable_id' => $meeting->id]);
        FileableFile::factory()->report()->create([
            'fileable_type' => MorphMap::alias(Meeting::class), 'fileable_id' => $meeting->id, 'deleted_externally_at' => now(),
        ]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.recentMeetings.0.vote_matches', 1)
                ->where('overview.recentMeetings.0.has_protocol', true)
                ->where('overview.recentMeetings.0.has_report', false)
                ->where('overview.recentMeetings.0.agenda_items_count', 1)
                ->where('overview.recentMeetings.0.can_discard', false));
    });

    test('term editing loads full metadata separately from member lists', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $duty = Duty::factory()->for($institution)->create();
        $member = User::factory()->create(['email' => 'member@example.test', 'phone' => '+37060000000']);
        $duty->users()->attach($member, [
            'start_date' => today()->subDay(),
            'additional_email' => 'term@example.test',
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        ]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('assignmentTerms')
                ->missing('overview.current_users.0.email')
                ->missing('overview.current_users.0.phone')
                ->missing('overview.current_users.0.pivot.additional_email')
                ->reloadOnly('assignmentTerms', fn (Assert $terms) => $terms
                    ->where('assignmentTerms.0.additional_email', 'term@example.test')
                    ->where('assignmentTerms.0.description', ['lt' => 'Aprašymas', 'en' => 'Description'])
                    ->whereType('assignmentTerms.0.start_date', 'string')));
    });

    test('the record carries per-record permissions and loads management only when requested', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowInstitution')
                ->where('can.update', true)
                ->where('can.recordMeeting', true)
                ->missing('management')
                ->reloadOnly('management', fn ($panels) => $panels
                    ->has('management.cadences')
                    ->has('management.globalCadences')
                    ->has('management.secretaryRosters')
                    ->has('management.studyPrograms')));
    });

    test('the record payload preserves localized values and types every optional panel', function (string $locale, string $name, string $dutyName): void {
        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => ['lt' => 'Taryba', 'en' => 'Council'],
            'short_name' => null,
            'description' => null,
        ]);
        $type = InstitutionType::factory()->create(['title' => ['lt' => 'Komitetas', 'en' => 'Committee'], 'extra_attributes' => null]);
        $institution->types()->attach($type);
        $duty = Duty::factory()->create([
            'institution_id' => $institution->id,
            'name' => ['lt' => 'Narys', 'en' => 'Member'],
            'description' => null,
        ]);
        $member = User::factory()->create(['email' => null, 'profile_photo_path' => null, 'pronouns' => ['en' => 'he/him']]);
        $duty->users()->attach($member, ['start_date' => today()->subDay(), 'end_date' => null, 'use_original_duty_name' => true]);
        $meeting = Meeting::factory()->hasAttached($institution)->create(['type' => null]);
        $task = $institution->tasks()->create(['name' => 'Contract task']);
        $task->users()->attach($member);
        $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id, 'responsible_user_id' => null]);
        $institution->problems()->attach($problem);
        $file = FileableFile::factory()->create(['fileable_type' => 'institution', 'fileable_id' => $institution->id, 'public_link' => null]);
        $cadence = Cadence::factory()->create(['institution_id' => $institution->id, 'start_date' => today()->subMonth(), 'end_date' => today()->addMonth()]);
        InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $member->id]);
        StudyProgram::factory()->create(['tenant_id' => $this->tenant->id, 'name' => ['lt' => 'Matematika', 'en' => 'Mathematics']]);
        $other = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        InstitutionLink::create(['source_institution_id' => $institution->id, 'target_institution_id' => $other->id, 'kind' => 'related', 'mutual' => false]);
        $history = InstitutionActivityRequest::factory()->create(['institution_id' => $institution->id, 'recipient_id' => $this->admin->id]);

        Comment::factory()->create([
            'commentable_type' => MorphMap::alias(Institution::class),
            'commentable_id' => $institution->id,
            'user_id' => $member->id,
        ]);
        asUser($this->admin);
        app()->setLocale($locale);
        $meeting->update(['start_time' => now()->subDay()]);

        asUserWithInertia($this->admin)->withSession(['lang' => $locale])->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/People/ShowInstitution')
                ->where('readOnly', false)
                ->where('institution.id', $institution->id)
                ->where('institution.tenant.id', $this->tenant->id)
                ->where('institution.duties_count', 1)
                ->where('institution.meetings_count', 1)
                ->where('institution.problems_count', 1)
                ->where('institution.has_files', true)
                ->where('canViewActivityRequests', true)
                ->where('can.update', true)
                ->where('institution.name', $name)
                ->whereType('institution.id', 'string')
                ->whereType('institution.is_active', 'boolean')
                ->missing('institution.short_name')
                ->whereType('institution.description', 'string|null')
                ->whereType('institution.types.0.id', 'integer')
                ->where('institution.types.0.extra_attributes', null)
                ->whereType('institution.governance_scope', 'string')
                ->missing('institution.tasks_count')
                ->missing('institution.comments_count')
                ->whereType('institution.secretaries.0.id', 'string')
                ->where('institution.secretaries.0.email', null)
                ->whereType('overview.current_users.0.id', 'string')
                ->missing('overview.current_users.0.email')
                ->missing('overview.current_users.0.phone')
                ->where('overview.current_users.0.pivot.end_date', null)
                ->missing('overview.duties')
                ->whereType('overview.activity_status.status', 'string')
                ->whereType('overview.activity_status.requires_action', 'boolean')
                ->where('overview.recentMeetings.0.id', $meeting->id)
                ->where('overview.recentMeetings.0.type', null)
                ->missing('tasks')->missing('files')->missing('management')->missing('activityRequests')
                ->reloadOnly(['duties', 'tasks', 'problems', 'relatedInstitutions', 'institutionLinks', 'management'], fn (Assert $panels) => $panels
                    ->has('duties', 1)
                    ->where('duties.0.id', $duty->id)
                    ->where('duties.0.current_users.0.id', $member->id)
                    ->has('tasks', 1)
                    ->has('problems', 1)
                    ->where('problems.0.id', $problem->id)
                    ->has('relatedInstitutions', 1)
                    ->has('institutionLinks', 1)
                    ->missing('duties.0.current_users.0.email')
                    ->missing('duties.0.current_users.0.pivot.additional_email')
                    ->where('duties.0.name', $dutyName)
                    ->where('duties.0.description', '')
                    ->whereType('duties.0.current_users.0.pivot.start_date', 'string')
                    ->where('tasks.0.id', $task->id)
                    ->where('tasks.0.users.0.profile_photo_path', null)
                    ->whereType('tasks.0.can_update', 'boolean')
                    ->whereType('tasks.0.taskable.name', 'string')
                    ->where('problems.0.responsible_user', null)
                    ->whereType('problems.0.status', 'string')
                    ->where('relatedInstitutions.0.kind', 'related')
                    ->whereType('relatedInstitutions.0.is_active', 'boolean')
                    ->where('institutionLinks.0.other.id', $other->id)
                    ->whereType('management.cadences.0.label', 'string')
                    ->where('management.cadences.0.start_meeting', null)
                    ->whereType('management.secretaryRosters.0.is_current', 'boolean')
                    ->whereType('management.studyPrograms.0.name', 'string'))
                ->reloadOnly(['files'], fn (Assert $files) => $files
                    ->has('files', 1)
                    ->missing('files.0.deleted_at')
                    ->where('files.0.id', $file->id)
                    ->where('files.0.public_link', null)
                    ->whereType('files.0.formatted_size', 'string'))
                ->reloadOnly('activityRequests', fn (Assert $requests) => $requests
                    ->where('activityRequests.data.0.id', $history->send_id)
                    ->where('activityRequests.data.0.campaigns.activity_confirmation.0.campaign_type', 'activity_confirmation')
                    ->where('activityRequests.data.0.campaigns.activity_confirmation.0.answer', null)
                    ->whereType('activityRequests.data.0.campaigns.activity_confirmation.0.automatic', 'boolean')
                    ->where('activityRequests.next_page', null)));
    })->with([
        'Lithuanian' => ['lt', 'Taryba', 'Narys'],
        'English' => ['en', 'Council', 'Member'],
    ]);

    test('someone who may only view the institution is never sent the management group', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $viewer = makeUser($this->tenant);
        $viewer->duties()->first()->assignRole('Studentų atstovas');
        $viewer->duties()->first()->update(['institution_id' => $institution->id]);

        asUserWithInertia($viewer)->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('can.update', false)
                ->where('can.recordMeeting', true)
                ->where('can.reportActivity', true)
                ->reloadOnly('management', fn ($panels) => $panels->where('management', null)));
    });

    test('the problems tab lists the institution\'s problems, unresolved first, after the first paint', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $resolved = Problem::factory()->resolved()->create(['tenant_id' => $this->tenant->id, 'occurred_at' => now()->subDay()]);
        $open = Problem::factory()->create(['tenant_id' => $this->tenant->id, 'occurred_at' => now()->subMonth()]);
        Problem::factory()->create(['tenant_id' => $this->tenant->id]);
        $institution->problems()->attach([$resolved->id, $open->id]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('institution.problems_count', 2)
                ->missing('problems')
                ->reloadOnly('problems', fn ($panels) => $panels
                    ->where('problems.0.id', $open->id)
                    ->where('problems.1.id', $resolved->id)
                    ->has('problems', 2)));
    });

    test('loads institution and meeting tasks on request', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $meeting = Meeting::factory()->hasAttached($institution)->create(['title' => 'Test Meeting Title']);
        $institutionTask = $institution->tasks()->create(['name' => 'Institution task']);
        $meetingTask = $meeting->tasks()->create(['name' => 'Meeting task']);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->missing('tasks')
            ->reloadOnly('tasks', fn (Assert $partial) => $partial
                ->has('tasks', 2)
                ->where('tasks', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
                    === collect([$institutionTask->id, $meetingTask->id])->sort()->values()->all()
                    && collect($rows)->firstWhere('id', $meetingTask->id)['taskable'] === [
                        'id' => $meeting->id,
                        'name' => 'Test Meeting Title',
                        'type' => 'meeting',
                    ])));
    });

    test('keeps institution types but omits unused overview comments', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $type = InstitutionType::factory()->create([]);
        $institution->types()->attach($type);

        $comment = Comment::factory()->create([
            'commentable_type' => MorphMap::alias(Institution::class),
            'commentable_id' => $institution->id,
            'body' => '<p>Hello overview</p>',
        ]);

        DB::table('comments')->where('id', $comment->id)->update(['created_at' => null]);

        $response = asUser($this->admin)->get(route('institutions.show', $institution));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowInstitution')
                ->has('institution.types', 1)
                ->missing('overview.recentComments')
            );
    });

    test('sends meeting counts without unused agenda titles', function (): void {
        $this->freezeTime();
        $institution = Institution::factory()->for($this->tenant)->create();
        $past = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->subDay()]);
        $next = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->addDay()]);

        $past->agendaItems()->create(['title' => 'Past agenda', 'order' => 1]);
        $next->agendaItems()->create(['title' => 'Upcoming agenda', 'order' => 1]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('overview.recentMeetings', 1)
            ->where('overview.recentMeetings.0.id', $past->id)
            ->where('overview.recentMeetings.0.agenda_items_count', 1)
            ->missing('overview.recentMeetings.0.agenda_item_titles')
            ->missing('overview.recentMeetings.0.agenda_items')
            ->where('upcomingMeeting.id', $next->id)
            ->where('upcomingMeeting.agenda_items_count', 1));
    });

    test('can index institutions', function (): void {
        $response = asUser($this->admin)->get(route('institutions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/People/IndexInstitution'));
    });

    // The live list reads from Typesense; only what the user could restore is counted here.
    test('the live index carries no rows, only the trash count', function (): void {
        Institution::factory()->create(['tenant_id' => $this->tenant->id])->delete();

        asUserWithInertia($this->admin)->get(route('institutions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexInstitution')
                ->where('deletedCount', 1)
                ->missing('data'));
    });

    test('the index carries the ids the user follows, since Typesense cannot know them', function (): void {
        $followed = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->admin->followedInstitutions()->attach($followed);

        asUserWithInertia($this->admin)->get(route('institutions.index'))
            ->assertInertia(fn ($page) => $page->where('followedInstitutionIds', [$followed->id]));
    });

    test('the trash is the same collection, fed from the database', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $institution->delete();

        asUserWithInertia($this->admin)->get(route('institutions.index', ['showDeleted' => 'true']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/IndexInstitution')
                ->where('deletedCount', 1));

        asUser($this->admin)->getJson(route('api.v1.admin.trash.index', ['collection' => 'institutions']))
            ->assertOk()
            ->assertJsonPath('data.items.0.id', (string) $institution->id);
    });

    test('can access institution create page', function (): void {
        $response = asUser($this->admin)->get(route('institutions.create'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/CreateInstitution')
            );
    });

    test('inline institution creation via API returns the created model', function (): void {
        $response = asUser($this->admin)->postJson(route('api.v1.admin.institutions.store'), [
            'name' => ['lt' => 'Vedlio institucija', 'en' => 'Wizard institution'],
            'tenant_id' => $this->tenant->id,
        ]);

        $created = Institution::where('name->lt', 'Vedlio institucija')->sole();
        $response->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.institution.id', $created->id)
            ->assertJsonPath('data.institution.tenant.shortname', $this->tenant->shortname);
    });

    test('the page and the wizard create the same institution', function (string $routeName, int $status): void {
        $type = InstitutionType::factory()->create();

        asUser($this->admin)->postJson(route($routeName), [
            'name' => ['lt' => 'Bendra institucija', 'en' => 'Shared institution'],
            'tenant_id' => $this->tenant->id,
            'types' => [$type->id],
            'image_media' => null,
            'logo_media' => null,
        ])->assertStatus($status);

        $created = Institution::where('name->lt', 'Bendra institucija')->sole();
        expect($created->types()->pluck('institution_types.id')->all())->toBe([$type->id])
            ->and(Activity::query()->whereMorphedTo('subject', $created)->where('event', 'relation_updated')->where('properties->relation', 'types')->exists())->toBeTrue();
    })->with([
        'page' => ['institutions.store', 302],
        'wizard API' => ['api.v1.admin.institutions.store', 201],
    ]);

    test('can store institution', function (): void {
        $institutionData = [
            'name' => ['lt' => 'Test Institution', 'en' => 'Test Institution EN'],
            'short_name' => ['lt' => 'TI', 'en' => 'TI'],
            'working_hours' => ['lt' => 'I–V 9:00–17:00', 'en' => 'Mon–Fri 9:00–17:00'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test-institution',
        ];

        $response = asUser($this->admin)->post(route('institutions.store'), $institutionData);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('institutions', [
            'alias' => 'test-institution',
            'tenant_id' => $this->tenant->id,
        ]);

        expect(Institution::query()->where('alias', 'test-institution')->firstOrFail()
            ->getTranslation('working_hours', 'lt'))->toBe('I–V 9:00–17:00');

        $created = Institution::where('alias', 'test-institution')->sole();
        $response->assertRedirectToRoute('institutions.show', $created)->assertSessionHas('success');
        asUser($this->admin)->get(route('institutions.show', $created))->assertOk();
    });

    test('can access institution edit page', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->admin)->get(route('institutions.edit', $institution));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/EditInstitution')
                ->has('institution')
            );
    });

    test('can update institution', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'name' => ['lt' => 'Updated Institution', 'en' => 'Updated Institution EN'],
            'short_name' => ['lt' => 'UI', 'en' => 'UI'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'updated-institution',
        ];

        $response = asUser($this->admin)->put(route('institutions.update', $institution), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('institutions', [
            'id' => $institution->id,
            'alias' => 'updated-institution',
        ]);
    });

    test('can deactivate an institution and keep managing its admin record', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);

        asUser($this->admin)->put(route('institutions.update', $institution), [
            'name' => ['lt' => 'Neaktyvi institucija', 'en' => 'Inactive institution'],
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('institutions', ['id' => $institution->id, 'is_active' => false]);
        asUser($this->admin)->get(route('institutions.show', $institution))->assertOk();
        asUser($this->admin)->get(route('institutions.edit', $institution))->assertOk();
    });

    test('can delete institution', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->admin)->delete(route('institutions.destroy', $institution));

        $response->assertRedirect();

        $this->assertSoftDeleted('institutions', [
            'id' => $institution->id,
        ]);
    });
});

describe('validation', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    test('requires name for store', function (): void {
        $response = asUser($this->admin)->post(route('institutions.store'), [
            'short_name' => ['lt' => 'TI'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test-alias',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name.lt');
    });

    test('the short name is optional', function (): void {
        asUser($this->admin)
            ->post(route('institutions.store'), ['name' => ['lt' => 'Chemijos taryba'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasNoErrors();
    });

    test('the alias is optional and made from the name', function (): void {
        asUser($this->admin)
            ->post(route('institutions.store'), ['name' => ['lt' => 'Chemijos studijų komitetas'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasNoErrors();

        expect(Institution::query()->where('name->lt', 'Chemijos studijų komitetas')->value('alias'))->toBe('chemijos-studiju-komitetas');
    });

    test('the name and short name must be unique among live institutions', function (): void {
        Institution::factory()->create(['name' => ['lt' => 'Fakulteto taryba', 'en' => 'Faculty council'], 'short_name' => ['lt' => 'FT', 'en' => 'FC']]);
        Institution::factory()->create(['name' => ['lt' => 'Ištrinta taryba', 'en' => 'Deleted council']])->delete();

        asUser($this->admin)
            ->post(route('institutions.store'), ['name' => ['lt' => 'Fakulteto taryba'], 'short_name' => ['lt' => 'FT'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasErrors(['name.lt', 'short_name.lt']);

        asUser($this->admin)
            ->post(route('institutions.store'), ['name' => ['lt' => 'Ištrinta taryba'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasNoErrors();
    });

    test('an edited institution keeps its own name but cannot take another\'s', function (): void {
        Institution::factory()->for($this->tenant)->create(['name' => ['lt' => 'Fakulteto taryba', 'en' => 'Faculty council'], 'short_name' => ['lt' => 'FT', 'en' => 'FC']]);
        $institution = Institution::factory()->for($this->tenant)->create(['name' => ['lt' => 'Studijų komitetas', 'en' => 'Study committee'], 'short_name' => ['lt' => 'SK', 'en' => 'SC']]);

        asUser($this->admin)
            ->put(route('institutions.update', $institution), ['name' => ['lt' => 'Studijų komitetas'], 'short_name' => ['lt' => 'SK'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasNoErrors();

        asUser($this->admin)
            ->put(route('institutions.update', $institution), ['name' => ['lt' => 'Fakulteto taryba'], 'short_name' => ['lt' => 'FT'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasErrors(['name.lt', 'short_name.lt']);
    });

    test('requires unique alias for store', function (): void {
        Institution::factory()->create(['alias' => 'existing-alias']);

        $response = asUser($this->admin)->post(route('institutions.store'), [
            'name' => ['lt' => 'Test Institution'],
            'short_name' => ['lt' => 'TI'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'existing-alias',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('alias');
    });

    test('requires name for update', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->admin)->put(route('institutions.update', $institution), [
            'short_name' => ['lt' => 'UI'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'updated-alias',
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('name.lt');
    });
});

describe('relationships', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    test('cannot edit institution from different tenant', function (): void {
        // Test that a regular tenant user cannot edit an institution from a different tenant
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);

        // Use the admin from OUR tenant (this.tenant) trying to access OTHER tenant's institution
        $response = asUser($this->admin)->get(route('institutions.edit', $otherInstitution));

        // Regular tenant user should be forbidden from accessing other tenant's institutions
        $response->assertStatus(403);
    });
});

describe('meeting_periodicity_days', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    test('can store institution with meeting_periodicity_days', function (): void {
        $institutionData = [
            'name' => ['lt' => 'Test Institution', 'en' => 'Test Institution EN'],
            'short_name' => ['lt' => 'TI', 'en' => 'TI'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test-periodicity-institution',
            'meeting_periodicity_days' => 45,
        ];

        $response = asUser($this->admin)->post(route('institutions.store'), $institutionData);

        $response->assertRedirect();

        $this->assertDatabaseHas('institutions', [
            'alias' => 'test-periodicity-institution',
            'meeting_periodicity_days' => 45,
        ]);
    });

    test('can update institution meeting_periodicity_days', function (): void {
        // Use a unique alias to avoid conflicts with seeded data
        $uniqueAlias = 'test-periodicity-update-'.uniqid();

        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => null,
            'alias' => $uniqueAlias,
        ]);

        $updateData = [
            'name' => ['lt' => $institution->getTranslation('name', 'lt'), 'en' => ''],
            'short_name' => ['lt' => '', 'en' => ''],
            'tenant_id' => $this->tenant->id,
            'alias' => $uniqueAlias,
            'meeting_periodicity_days' => 60,
        ];

        $response = asUser($this->admin)->put(route('institutions.update', $institution), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $institution->refresh();
        expect($institution->getRawOriginal('meeting_periodicity_days'))->toBe(60);
    });

    test('can set meeting_periodicity_days to null to revert to type inheritance', function (): void {
        // Use a unique alias to avoid conflicts with seeded data
        $uniqueAlias = 'test-periodicity-null-'.uniqid();

        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => 45,
            'alias' => $uniqueAlias,
        ]);

        $updateData = [
            'name' => ['lt' => $institution->getTranslation('name', 'lt'), 'en' => ''],
            'short_name' => ['lt' => '', 'en' => ''],
            'tenant_id' => $this->tenant->id,
            'alias' => $uniqueAlias,
            'meeting_periodicity_days' => null,
        ];

        $response = asUser($this->admin)->put(route('institutions.update', $institution), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $institution->refresh();
        expect($institution->getRawOriginal('meeting_periodicity_days'))->toBeNull();
    });

    test('accessor returns institution override when set', function (): void {
        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => 90,
        ]);

        expect($institution->meeting_periodicity_days)->toBe(90);
    });

    test('accessor returns type periodicity when institution override is null', function (): void {
        $type = InstitutionType::factory()->create([
            'extra_attributes' => ['meeting_periodicity_days' => 14],
        ]);

        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => null,
        ]);
        $institution->types()->attach($type);

        expect($institution->meeting_periodicity_days)->toBe(14);
    });

    test('accessor returns minimum type periodicity when multiple types', function (): void {
        $type1 = InstitutionType::factory()->create([
            'extra_attributes' => ['meeting_periodicity_days' => 30],
        ]);
        $type2 = InstitutionType::factory()->create([
            'extra_attributes' => ['meeting_periodicity_days' => 14],
        ]);

        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => null,
        ]);
        $institution->types()->attach([$type1->id, $type2->id]);

        // Should return the minimum (14)
        expect($institution->meeting_periodicity_days)->toBe(14);
    });

    test('accessor returns default 30 when no override and no type periodicity', function (): void {
        $type = InstitutionType::factory()->create([
            'extra_attributes' => [], // No periodicity set
        ]);

        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => null,
        ]);
        $institution->types()->attach($type);

        expect($institution->meeting_periodicity_days)->toBe(30);
    });

    test('accessor returns default 30 when no types attached', function (): void {
        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => null,
        ]);

        expect($institution->meeting_periodicity_days)->toBe(30);
    });

    test('show endpoint keeps periodicity in the rendered activity summary', function (): void {
        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => 21,
        ]);

        $response = asUser($this->admin)->get(route('institutions.show', $institution));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowInstitution')
                ->missing('institution.meeting_periodicity_days')
                ->where('overview.activity_status.periodicity_days', 21)
            );
    });

    test('show endpoint returns computed periodicity when override is null', function (): void {
        $type = InstitutionType::factory()->create([
            'extra_attributes' => ['meeting_periodicity_days' => 7],
        ]);

        $institution = Institution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => null,
        ]);
        $institution->types()->attach($type);

        $response = asUser($this->admin)->get(route('institutions.show', $institution));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowInstitution')
                ->missing('institution.meeting_periodicity_days')
                ->where('overview.activity_status.periodicity_days', 7)
            );
    });

    test('show endpoint states which governance world the body belongs to', function (): void {
        $type = InstitutionType::factory()->create([
            'extra_attributes' => ['governance_scope' => 'vusa'],
        ]);

        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $institution->types()->attach($type);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/People/ShowInstitution')
                ->where('institution.governance_scope', 'vusa')
            );
    });

    test('an untyped body still states a scope, so the badge is never blank', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUserWithInertia($this->admin)->get(route('institutions.show', $institution))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('institution.governance_scope', 'vu')
            );
    });

    test('validates meeting_periodicity_days is positive integer', function (): void {
        $response = asUser($this->admin)->post(route('institutions.store'), [
            'name' => ['lt' => 'Test Institution'],
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => -5,
        ]);

        $response->assertSessionHasErrors('meeting_periodicity_days');
    });

    test('validates meeting_periodicity_days max is 365', function (): void {
        $response = asUser($this->admin)->post(route('institutions.store'), [
            'name' => ['lt' => 'Test Institution'],
            'tenant_id' => $this->tenant->id,
            'meeting_periodicity_days' => 500,
        ]);

        $response->assertSessionHasErrors('meeting_periodicity_days');
    });
});

describe('institution search indexing', function (): void {
    test('searchable array exposes duty names and current member names', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Testinė institucija', 'en' => 'Test Institution'],
        ]);
        $duty = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
        ]);
        $member = User::factory()->create(['name' => 'Jonas Jonaitis']);
        $duty->users()->attach($member->id, ['start_date' => now()->subYear(), 'end_date' => null]);

        $searchable = $institution->fresh()->toSearchableArray();

        expect($searchable)->toHaveKeys(['name_lt', 'duty_names', 'current_user_names'])
            ->and($searchable['duty_names'])->toContain('Pirmininkas')
            ->and($searchable['current_user_names'])->toContain('Jonas Jonaitis');
    });

    test('searchable array carries the activity status the ViSAK numbers filter by', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();

        expect($institution->fresh()->toSearchableArray()['activity_status'])->toBe('no_activity');

        Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->addWeek()]);

        expect($institution->fresh()->toSearchableArray()['activity_status'])->toBe('covered_by_upcoming_meeting');
    });
});

describe('padalinys, deletion and trash', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $this->otherTenant = Tenant::factory()->create();
    });

    test('an institution cannot be created in a padalinys outside the editor\'s reach', function (): void {
        asUser($this->admin)
            ->post(route('institutions.store'), ['name' => ['lt' => 'Svetima taryba'], 'tenant_id' => $this->otherTenant->id])
            ->assertSessionHasErrors('tenant_id');

        expect(Institution::query()->where('name->lt', 'Svetima taryba')->exists())->toBeFalse();
    });

    test('only a super admin moves an institution to another padalinys', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $payload = ['name' => ['lt' => 'Perkelta taryba'], 'tenant_id' => $this->otherTenant->id];

        asUser($this->admin)->put(route('institutions.update', $institution), $payload);

        expect($institution->fresh()->tenant_id)->toBe($this->tenant->id);

        asUser(makeAdminUser($this->tenant))->put(route('institutions.update', $institution), $payload);

        expect($institution->fresh()->tenant_id)->toBe($this->otherTenant->id);
    });

    test('an editor cannot delete an institution they are a member of', function (): void {
        $ownInstitution = $this->admin->duties()->first()->institution;

        asUser($this->admin)
            ->delete(route('institutions.destroy', $ownInstitution))
            ->assertSessionHas('error');

        expect($ownInstitution->fresh()->trashed())->toBeFalse();
    });

    test('a student representative coordinator creates and edits institutions but cannot delete them', function (): void {
        $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
        $institution = Institution::factory()->for($this->tenant)->create();

        asUser($coordinator)->get(route('institutions.create'))->assertOk();
        asUser($coordinator)->get(route('institutions.edit', $institution))->assertOk();
        asUser($coordinator)->delete(route('institutions.destroy', $institution))->assertForbidden();
    });

    test('a deleted institution can be restored from the trash', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->delete();

        asUser($this->admin)->patch(route('institutions.restore', $institution))->assertRedirect(route('institutions.show', $institution));

        expect($institution->fresh()->trashed())->toBeFalse();
    });

    test('restoring an untitled legacy institution opens an identifiable record without changing its title', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->delete();
        DB::table('institutions')->where('id', $institution->id)->update(['name' => null, 'short_name' => null]);
        $institution = $institution->fresh();

        asUser($this->admin)->from(route('institutions.index', ['showDeleted' => true]))
            ->patch(route('institutions.restore', $institution))
            ->assertRedirect(route('institutions.show', $institution))->assertSessionHas('success');

        asUser($this->admin)->get(route('institutions.show', $institution))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('institution.name', __('Be pavadinimo').' · '.$institution->alias));
        expect($institution->fresh()->getRawOriginal('name'))->toBeNull();
    });

    test('an institution with duties or check-ins cannot be deleted permanently', function (): void {
        $superAdmin = makeAdminUser($this->tenant);
        $withDuty = Institution::factory()->for($this->tenant)->has(Duty::factory())->create();
        $withCheckIn = Institution::factory()->for($this->tenant)->create();
        InstitutionCheckIn::factory()->for($withCheckIn)->create();

        foreach ([$withDuty, $withCheckIn] as $institution) {
            $institution->delete();

            asUser($superAdmin)
                ->delete(route('institutions.forceDelete', $institution))
                ->assertSessionHas('error');

            expect(Institution::withTrashed()->find($institution->id))->not->toBeNull();
        }
    });
});

describe('alias', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    });

    $update = fn (Institution $institution, string $name, string $alias): array => [
        'name' => ['lt' => $name, 'en' => $name],
        'tenant_id' => $institution->tenant_id,
        'alias' => $alias,
    ];

    test('renaming moves an untouched alias along and keeps the old address on record', function () use ($update): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id, 'name' => ['lt' => 'Senas pavadinimas'], 'alias' => 'senas-pavadinimas']);

        asUser($this->admin)->put(route('institutions.update', $institution), $update($institution, 'Naujas pavadinimas', 'senas-pavadinimas'))
            ->assertSessionHasNoErrors();

        expect($institution->refresh()->alias)->toBe('naujas-pavadinimas')
            ->and($institution->publicUrls()->pluck('url')->all())->each->toEndWith('/senas-pavadinimas');

        asUserWithInertia($this->admin)->get(route('institutions.edit', $institution))
            ->assertInertia(fn ($page) => $page->has('institution.public_urls', 2)->where('institution.alias_pinned', false));
    });

    test('a hand-edited alias wins over the name', function () use ($update): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id, 'name' => ['lt' => 'Senas pavadinimas']]);

        asUser($this->admin)->put(route('institutions.update', $institution), $update($institution, 'Naujas pavadinimas', 'mano-adresas'))
            ->assertSessionHasNoErrors();

        expect($institution->refresh()->alias)->toBe('mano-adresas');
    });

    test('a tenant’s own institution keeps the tenant alias through a rename', function () use ($update): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id, 'alias' => $this->tenant->alias]);

        asUser($this->admin)->put(route('institutions.update', $institution), $update($institution, 'Kitas pavadinimas', 'kitas'))
            ->assertSessionHasNoErrors();

        expect($institution->refresh()->alias)->toBe($this->tenant->alias);
    });

    test('a taken slug on create gets the tenant prefix', function (): void {
        Institution::factory()->create(['alias' => 'studiju-kolegija']);

        asUser($this->admin)->post(route('institutions.store'), ['name' => ['lt' => 'Studijų kolegija'], 'tenant_id' => $this->tenant->id])
            ->assertSessionHasNoErrors();

        expect(Institution::query()->where('tenant_id', $this->tenant->id)->where('name->lt', 'Studijų kolegija')->value('alias'))
            ->toBe("{$this->tenant->alias}-studiju-kolegija");
    });

    test('rejects an alias that is not lowercase, hyphenated ascii', function (string $alias) use ($update): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->admin)->put(route('institutions.update', $institution), $update($institution, 'Pavadinimas', $alias))
            ->assertSessionHasErrors('alias');
    })->with(['Programų sistemos (BA)', 'Didžiosios', 'ginčų-komisija', 'studentu-atstovai']);

    test('rejects an alias held by a trashed institution or still redirecting for another', function () use ($update): void {
        Institution::factory()->create(['alias' => 'ismestas'])->delete();
        $renamed = Institution::factory()->create(['alias' => 'buves-adresas']);
        $renamed->update(['alias' => 'dabartinis-adresas']);
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        asUser($this->admin)->put(route('institutions.update', $institution), $update($institution, 'Pavadinimas', 'ismestas'))
            ->assertSessionHasErrors(['alias' => __('validation.unique_trashed', ['attribute' => __('validation.attributes.alias')])]);
        asUser($this->admin)->put(route('institutions.update', $institution), $update($institution, 'Pavadinimas', 'buves-adresas'))
            ->assertSessionHasErrors('alias');
    });

    test('deletes an old address of its own institution only', function (): void {
        $institution = Institution::factory()->create(['tenant_id' => $this->tenant->id, 'alias' => 'pirmas']);
        $institution->update(['alias' => 'antras']);
        $other = Institution::factory()->create(['alias' => 'kito-pirmas']);
        $other->update(['alias' => 'kito-antras']);

        asUser($this->admin)->delete(route('institutions.publicUrls.destroy', [$institution, $other->publicUrls()->first()]))
            ->assertNotFound();

        asUser($this->admin)->delete(route('institutions.publicUrls.destroy', [$institution, $institution->publicUrls()->first()]))
            ->assertRedirect();

        expect($institution->publicUrls()->count())->toBe(1)
            ->and($other->publicUrls()->count())->toBe(2);
    });

    test('a user who cannot update the institution cannot delete its old addresses', function (): void {
        $institution = Institution::factory()->create(['alias' => 'pirmas']);
        $institution->update(['alias' => 'antras']);

        asUser(makeUser($this->tenant))->delete(route('institutions.publicUrls.destroy', [$institution, $institution->publicUrls()->first()]))
            ->assertForbidden();
    });
});

test('institution overview batches private agenda access for a padalinys coordinator', function (): void {
    $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $institution = Institution::factory()->for($this->tenant)->create();
    $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => now()->subDays(3)]);
    $addItems = fn (int $count) => AgendaItem::factory()->for($meeting)->count($count)->create(['is_private' => true]);
    $fetch = fn () => asUserWithInertia($coordinator)->getJson(route('institutions.show', $institution))->assertOk();

    $measure = function (int $expectedItems) use ($fetch, $meeting): int {
        $fetch();
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $fetch();
            $queries = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $response->assertJsonPath('props.readOnly', false)
            ->assertJsonPath('props.overview.recentMeetings.0.id', $meeting->id)
            ->assertJsonPath('props.overview.recentMeetings.0.agenda_items_count', $expectedItems)
            ->assertJsonPath('props.overview.recentMeetings.0.outcome.completion_status', fn ($status): bool => $status !== null);

        return $queries;
    };

    $addItems(1);
    $small = $measure(1);
    $addItems(4);

    expect($measure(5))->toBe($small);
});
