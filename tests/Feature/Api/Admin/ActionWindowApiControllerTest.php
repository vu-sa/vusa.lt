<?php

use App\Enums\InstitutionScope;
use App\Enums\MeetingType;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->institution = $this->user->duties()->first()->institution;
});

/** A meeting in the past attached to the given institution, optionally with agenda items. */
function pastMeetingFor(Institution $institution, int $daysAgo, int $agendaItems = 0): Meeting
{
    $meeting = Meeting::factory()->create(['start_time' => now()->subDays($daysAgo)]);
    $meeting->institutions()->attach($institution->id);

    for ($i = 1; $i <= $agendaItems; $i++) {
        AgendaItem::factory()->create(['meeting_id' => $meeting->id, 'order' => $i]);
    }

    return $meeting;
}

test('a guest is refused', function (): void {
    $this->getJson(route('api.v1.admin.actionWindow.context'))->assertUnauthorized();
});

test('it returns the caller institutions with their activity status', function (): void {
    $response = asUser($this->user)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk();

    $institutions = $response->json('data.institutions');

    expect($institutions)->toHaveCount(1)
        ->and($institutions[0]['id'])->toBe((string) $this->institution->id)
        ->and($institutions[0]['name'])->toBe($this->institution->name)
        ->and($institutions[0]['activity_status'])->toHaveKeys(['status', 'requires_action', 'priority']);
});

describe('meeting pattern', function (): void {
    test('it guesses the weekday and time from the meetings that actually happened', function (): void {
        // Three Tuesdays at 18:00 and one stray Friday: the Tuesday wins, and the time
        // comes from the most recent Tuesday rather than an average.
        foreach ([21, 14, 7] as $daysAgo) {
            pastMeetingFor($this->institution, $daysAgo)->update([
                'start_time' => now()->subDays($daysAgo)->next(Carbon::TUESDAY)->setTime(18, 0),
            ]);
        }
        pastMeetingFor($this->institution, 3)->update([
            'start_time' => now()->subDays(3)->next(Carbon::FRIDAY)->subWeek()->setTime(9, 30),
        ]);

        $pattern = asUser($this->user)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk()
            ->json('data.institutions.0.meeting_pattern');

        expect($pattern)->toBe(['weekday' => 2, 'time' => '18:00']);
    });

    test('an email meeting never sets the hour, because 23:59 is a deadline not a time', function (): void {
        pastMeetingFor($this->institution, 7)->update([
            'start_time' => now()->subDays(7)->setTime(23, 59),
            'type' => MeetingType::Email,
        ]);

        $pattern = asUser($this->user)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk()
            ->json('data.institutions.0.meeting_pattern');

        expect($pattern)->toBeNull();
    });

    test('a body that has never met gets no guess rather than a wrong one', function (): void {
        $pattern = asUser($this->user)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk()
            ->json('data.institutions.0.meeting_pattern');

        expect($pattern)->toBeNull();
    });
});

test('it reports whether an institution may be announced in the calendar', function (): void {
    $this->institution->types()->attach(
        InstitutionType::factory()->withGovernanceScope(InstitutionScope::University)->create()
    );

    $response = asUser($this->user)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk();

    expect($response->json('data.institutions.0.is_internal'))->toBeFalse();
});

test('it never leaks an institution the caller has no duty in', function (): void {
    $stranger = Institution::factory()->for($this->tenant)->create();
    pastMeetingFor($stranger, 10);

    $ids = collect(
        asUser($this->user)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk()
            ->json('data.institutions')
    )->pluck('id');

    expect($ids)->not->toContain((string) $stranger->id);
});

test('meetings with no agenda items are listed before merely incomplete ones', function (): void {
    $withoutAgenda = pastMeetingFor($this->institution, 5);
    $withAgenda = pastMeetingFor($this->institution, 3, agendaItems: 2);

    $meetings = asUser($this->user)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk()
        ->json('data.meetingsNeedingAttention');

    expect(collect($meetings)->pluck('id')->all())
        ->toBe([(string) $withoutAgenda->id, (string) $withAgenda->id])
        ->and($meetings[0]['completion_status'])->toBe('no_items')
        ->and($meetings[1]['completion_status'])->toBe('incomplete');
});

test('an upcoming meeting is not something to fill in yet', function (): void {
    $upcoming = Meeting::factory()->create(['start_time' => now()->addWeek()]);
    $upcoming->institutions()->attach($this->institution->id);

    $meetings = asUser($this->user)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk()
        ->json('data.meetingsNeedingAttention');

    expect(collect($meetings)->pluck('id'))->not->toContain((string) $upcoming->id);
});

test('a user with no duties gets empty lists rather than an error', function (): void {
    $dutiless = User::factory()->create();

    $response = asUser($dutiless)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk();

    expect($response->json('data.institutions'))->toBe([])
        ->and($response->json('data.meetingsNeedingAttention'))->toBe([]);
});

describe('check-in start', function (): void {
    beforeEach(function (): void {
        $this->user->duties()->updateExistingPivot($this->user->duties()->first()->id, ['start_date' => now()->subYear()]);
    });

    $startOf = fn (User $user): array => asUser($user)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk()
        ->json('data.institutions.0');

    test('it starts at the caller’s term when nothing has happened since', function () use ($startOf): void {
        pastMeetingFor($this->institution, 400);

        expect($startOf($this->user))->toMatchArray([
            'check_in_from' => now()->subYear()->toDateString(),
            'check_in_from_reason' => 'tenure_start',
        ]);
    });

    test('it starts the day after the last meeting within the term', function () use ($startOf): void {
        pastMeetingFor($this->institution, 10);

        expect($startOf($this->user))->toMatchArray([
            'check_in_from' => now()->subDays(9)->toDateString(),
            'check_in_from_reason' => 'last_meeting',
        ]);
    });

    test('it starts after an earlier check-in rather than overlapping it', function () use ($startOf): void {
        pastMeetingFor($this->institution, 30);
        InstitutionCheckIn::factory()->create([
            'institution_id' => $this->institution->id,
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(5),
        ]);

        expect($startOf($this->user))->toMatchArray([
            'check_in_from' => now()->subDays(4)->toDateString(),
            'check_in_from_reason' => 'last_check_in',
        ]);
    });

    test('a student representative starts at the term activity requests ask about', function () use ($startOf): void {
        $duty = Duty::factory()->for($this->institution)->create();
        grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
        $rep = User::factory()->create();
        $rep->duties()->attach($duty, ['start_date' => now()->subMonths(2), 'end_date' => null]);

        expect($startOf($rep))->toMatchArray([
            'check_in_from' => now()->subMonths(2)->toDateString(),
            'check_in_from_reason' => 'tenure_start',
        ]);
    });
});

describe('wider institution search', function (): void {
    /**
     * The window offers "another institution" only to someone who could actually file a
     * meeting there; for a plain representative the option would lead to a refusal.
     */
    test('it stays closed for a caller holding only their own duties', function (): void {
        $response = asUser($this->user)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk();

        expect($response->json('data.institutionSearch.enabled'))->toBeFalse()
            ->and($response->json('data.institutionSearch.tenant_ids'))->toBe([]);
    });

    test('it opens, scoped to their tenants, for a tenant-wide coordinator', function (): void {
        $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        $response = asUser($coordinator)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk();

        expect($response->json('data.institutionSearch.enabled'))->toBeTrue()
            ->and($response->json('data.institutionSearch.tenant_ids'))->toContain($this->tenant->id);
    });

    /**
     * A super admin may create anywhere, so an explicit tenant list would only be a
     * filter to fall out of date — an empty list means no filter.
     */
    test('it sends no tenant filter for an all-scope caller', function (): void {
        $superAdmin = makeAdminUser($this->tenant);

        $response = asUser($superAdmin)
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk();

        expect($response->json('data.institutionSearch.enabled'))->toBeTrue()
            ->and($response->json('data.institutionSearch.tenant_ids'))->toBe([]);
    });

    /** A meeting created from a calendar event narrows the search to VU SA's own bodies by type. */
    test('it sends the type ids that resolve to a VU SA body and to an external one', function (): void {
        $vusa = InstitutionType::factory()->withGovernanceScope(InstitutionScope::Vusa)->create();
        $vusaChild = InstitutionType::factory()->create(['parent_id' => $vusa->id]);
        $university = InstitutionType::factory()->withGovernanceScope(InstitutionScope::University)->create();

        $response = asUser(makeAdminUser($this->tenant))
            ->getJson(route('api.v1.admin.actionWindow.context'))
            ->assertOk();

        expect($response->json('data.institutionSearch.internal_type_ids'))->toContain($vusa->id, $vusaChild->id)
            ->not->toContain($university->id)
            ->and($response->json('data.institutionSearch.external_type_ids'))->toContain($university->id)
            ->not->toContain($vusa->id);
    });
});
