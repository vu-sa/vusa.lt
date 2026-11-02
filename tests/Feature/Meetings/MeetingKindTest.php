<?php

use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Support\MeetingTitle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->assemblyType = InstitutionType::factory()->create(['extra_attributes' => ['meeting_kind' => MeetingKind::Assembly->value]]);
});

describe('resolving the kind', function (): void {
    test('the institution override wins over its types, which win over a posėdis', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        expect($institution->resolvedMeetingKind())->toBe(MeetingKind::Sitting);

        $institution->types()->attach($this->assemblyType->id);
        expect($institution->fresh()->resolvedMeetingKind())->toBe(MeetingKind::Assembly);

        $institution->update(['meeting_kind' => MeetingKind::Meeting]);
        expect($institution->fresh()->resolvedMeetingKind())->toBe(MeetingKind::Meeting);
    });

    test('titles carry the kind in both locales, and an email meeting reads as a decision', function (): void {
        $start = Carbon::parse('2026-09-12 14:00');

        expect(MeetingTitle::build($start, MeetingType::InPerson, 'lt', MeetingKind::Assembly))->toBe('2026 rugsėjo 12 d. 14.00 val. susirinkimas')
            ->and(MeetingTitle::build($start, MeetingType::InPerson, 'en', MeetingKind::Assembly))->toBe('12 September 2026 14.00 assembly')
            ->and(MeetingTitle::build($start, MeetingType::Email, 'lt', MeetingKind::Assembly))->toBe('2026 rugsėjo 12 d. sprendimas el. paštu');
    });
});

describe('stored titles', function (): void {
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

    test('changing the institution override renames recorded meetings without marking them changed', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $meeting = Meeting::factory()->hasAttached($institution)->create(['title' => '', 'start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $meeting->forceFill(['updated_at' => now()->subWeek()])->saveQuietly();
        $updatedAt = $meeting->fresh()->updated_at;

        $institution->update(['meeting_kind' => MeetingKind::Meeting]);

        expect($meeting->fresh())
            ->title->toBe('2026 rugsėjo 12 d. 14.00 val. susitikimas')
            ->updated_at->toEqual($updatedAt);
    });

    test('changing a type kind renames the meetings of its institutions', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->types()->attach($this->assemblyType->id);
        $meeting = Meeting::factory()->hasAttached($institution)->create(['title' => '', 'start_time' => '2026-09-12 14:00', 'type' => MeetingType::InPerson]);
        $meeting->refreshTitle();

        $this->assemblyType->update(['extra_attributes' => ['meeting_kind' => MeetingKind::Conference->value]]);

        expect($meeting->fresh()->title)->toBe('2026 rugsėjo 12 d. 14.00 val. konferencija');
    });
});
