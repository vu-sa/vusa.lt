<?php

use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Events\SearchRelationChanged;
use App\Jobs\SyncMeetingOutcomeIndex;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\PublicMeeting;
use App\Models\Tenant;
use App\Support\MeetingTitle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->assemblyType = InstitutionType::factory()->create(['extra_attributes' => ['meeting_kind' => MeetingKind::Assembly->value]]);
});

describe('resolving the kind', function (): void {
    test('the meeting override wins over its institution types, which win over a posėdis', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        expect($institution->resolvedMeetingKind())->toBe(MeetingKind::Sitting);

        $institution->types()->attach($this->assemblyType->id);
        expect($institution->fresh()->resolvedMeetingKind())->toBe(MeetingKind::Assembly);

        $meeting = Meeting::factory()->hasAttached($institution)->create(['meeting_kind' => MeetingKind::Meeting]);
        expect($meeting->meetingKind())->toBe(MeetingKind::Meeting);
    });

    test('titles carry the kind in both locales, and an email meeting reads as a decision', function (): void {
        $start = Carbon::parse('2026-09-12 14:00');

        expect(MeetingTitle::build($start, MeetingType::InPerson, 'lt', MeetingKind::Assembly))->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas')
            ->and(MeetingTitle::build($start, MeetingType::InPerson, 'en', MeetingKind::Assembly))->toBe('12 September 2026 14.00 assembly')
            ->and(MeetingTitle::build($start, MeetingType::Email, 'lt', MeetingKind::Assembly))->toBe('2026 rugsėjo 12 d. sprendimas el. paštu');
    });
});

describe('stored titles', function (): void {
    test('trashing and restoring a kind-bearing type refreshes recorded titles and schedules reindexing', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->types()->attach($this->assemblyType);
        $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $meeting->refreshTitle();
        Bus::fake([SyncMeetingOutcomeIndex::class]);

        $this->assemblyType->delete();

        expect($meeting->fresh()->title)->toBe('2026 rugsėjo 12 d. 14.00 val. posėdis');
        Bus::assertDispatched(SyncMeetingOutcomeIndex::class, fn ($job) => $job->meetingId === $meeting->id);

        $this->assemblyType->restore();

        expect($meeting->fresh()->title)->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas');
    });

    test('institution type changes refresh trashed meetings before they can be restored', function (string $change): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $meeting->refreshTitle();
        $meeting->delete();

        $institution->types()->attach($this->assemblyType);
        SearchRelationChanged::dispatch($institution, $change);

        expect(Meeting::withTrashed()->findOrFail($meeting->id)->title)->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas');
    })->with(['types']);

    test('a meeting recorded through the form is named by its institution kind', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->types()->attach($this->assemblyType->id);

        asUser($admin)->post(route('meetings.store'), [
            'start_time' => '2026-09-12 14:00:00',
            'institution_id' => $institution->id,
        ])->assertSessionHasNoErrors();

        expect($institution->meetings()->sole()->title)->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas');
    });

    test('editing a meeting kind renames only that meeting and null restores its type default', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->types()->attach($this->assemblyType);
        $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $other = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $other->refreshTitle();

        asUser($admin)->patch(route('meetings.update', $meeting), [
            'start_time' => '2026-09-12 14:00:00',
            'meeting_kind' => MeetingKind::Meeting->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($meeting->fresh())->meeting_kind->toBe(MeetingKind::Meeting)
            ->title->toBe('2026 rugsėjo 12 d. 14.00 val. susitikimas');
        expect($other->fresh()->title)->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas');

        asUser($admin)->patch(route('meetings.update', $meeting), [
            'start_time' => '2026-09-12 14:00:00',
            'meeting_kind' => null,
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($meeting->fresh())->meeting_kind->toBeNull()
            ->title->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas');
    });

    test('an invalid meeting kind fails validation without renaming the meeting', function (): void {
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
        $institution = Institution::factory()->for($this->tenant)->create();
        $meeting = Meeting::factory()->hasAttached($institution)->create(['start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $title = $meeting->title;

        asUser($admin)->patch(route('meetings.update', $meeting), [
            'start_time' => '2026-09-12 14:00:00',
            'meeting_kind' => 'invalid',
        ])->assertSessionHasErrors('meeting_kind');

        expect($meeting->fresh())->meeting_kind->toBeNull()->title->toBe($title);
    });

    test('a new meeting uses its own kind before any institution is attached', function (): void {
        $meeting = Meeting::factory()->create([
            'title' => '',
            'start_time' => '2026-09-12 14:00',
            'type' => MeetingType::InPerson,
            'meeting_kind' => MeetingKind::Conference,
        ]);

        expect($meeting->title)->toBe('2026 rugsėjo 12 d. 14.00 val. konferencija');
    });

    test('changing a type kind renames the meetings of its institutions', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->types()->attach($this->assemblyType->id);
        $meeting = Meeting::factory()->hasAttached($institution)->create(['title' => '', 'start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $meeting->refreshTitle();

        $meeting->forceFill(['updated_at' => now()->subWeek()])->saveQuietly();
        $updatedAt = $meeting->fresh()->updated_at;
        $overridden = Meeting::factory()->hasAttached($institution)->create([
            'start_time' => '2026-09-12 14:00',
            'type' => MeetingType::InPerson,
            'meeting_kind' => MeetingKind::Meeting,
        ]);

        $this->assemblyType->update(['extra_attributes' => ['meeting_kind' => MeetingKind::Conference->value]]);

        expect($meeting->fresh())->title->toBe('2026 rugsėjo 12 d. 14.00 val. konferencija')
            ->updated_at->toEqual($updatedAt);
        expect($overridden->fresh()->title)->toBe('2026 rugsėjo 12 d. 14.00 val. susitikimas');
    });
});

test('public search uses the same lead institution and kind in attachment order', function (bool $sameTime): void {
    $institutions = Institution::factory()->for($this->tenant)->count(2)->create()->sortBy('id')->values();
    $lead = $sameTime ? $institutions[0] : $institutions[1];
    $other = $sameTime ? $institutions[1] : $institutions[0];
    $lead->types()->attach($this->assemblyType);
    $meeting = Meeting::factory()->create(['start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
    $meeting->institutions()->attach($other, ['created_at' => '2026-09-02 12:00:00']);
    $meeting->institutions()->attach($lead, ['created_at' => $sameTime ? '2026-09-02 12:00:00' : '2026-09-01 12:00:00']);

    $public = PublicMeeting::findOrFail($meeting->id);

    expect($meeting->fresh()->institutions->first()->id)->toBe($lead->id)
        ->and($public->meetingKind())->toBe(MeetingKind::Assembly)
        ->and($public->toSearchableArray()['institution_id'])->toBe($lead->id);
})->with(['different attachment times' => false, 'tied attachment times' => true]);

test('joint headings use the correct noun and grammatical gender', function (MeetingKind $kind, string $lt, string $en): void {
    expect(MeetingTitle::noun(MeetingType::InPerson, 'lt', $kind, joint: true))->toBe($lt)
        ->and(MeetingTitle::noun(MeetingType::InPerson, 'en', $kind, joint: true))->toBe($en)
        ->and(MeetingTitle::noun(MeetingType::Email, 'lt', $kind, joint: true))->toBe('jungtinis sprendimas el. paštu')
        ->and(MeetingTitle::noun(MeetingType::Email, 'en', $kind, joint: true))->toBe('joint decision by email');
})->with([
    'sitting' => [MeetingKind::Sitting, 'jungtinis posėdis', 'joint meeting'],
    'assembly' => [MeetingKind::Assembly, 'jungtinis susirinkimas', 'joint assembly'],
    'meeting' => [MeetingKind::Meeting, 'jungtinis susitikimas', 'joint meeting'],
    'conference' => [MeetingKind::Conference, 'jungtinė konferencija', 'joint conference'],
]);

test('the edit sheet receives the inherited kind independently of the joint heading noun', function (bool $joint): void {
    app()->setLocale('lt');
    $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $institution = Institution::factory()->for($this->tenant)->create();
    $institution->types()->attach($this->assemblyType);
    $meeting = Meeting::factory()->hasAttached($institution)->create(['meeting_kind' => MeetingKind::Conference]);
    if ($joint) {
        $meeting->institutions()->attach(Institution::factory()->for($this->tenant)->create());
    }

    asUser($admin)->get(route('meetings.show', $meeting))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('meeting.title_noun', $joint ? 'jungtinė konferencija' : 'konferencija')
            ->where('meeting.meeting_kind', MeetingKind::Conference->value)
            ->where('meeting.inherited_meeting_kind', MeetingKind::Assembly->value));
})->with(['single' => false, 'joint' => true]);
