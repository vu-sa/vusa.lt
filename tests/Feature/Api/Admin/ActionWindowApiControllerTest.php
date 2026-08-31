<?php

use App\Enums\InstitutionScope;
use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
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
        Type::factory()->forInstitutions(InstitutionScope::University)->create()
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
        $coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

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
});
