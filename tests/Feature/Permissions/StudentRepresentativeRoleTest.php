<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

/**
 * What the seeded "Studentų atstovas" role lets a representative do with meetings: everything for
 * the institutions they hold a duty in, nothing for the rest of their padalinys.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->representative = makeTenantUserWithRole('Studentų atstovas', $this->tenant);
    $this->ownInstitution = $this->representative->duties()->firstOrFail()->institution;
    $this->otherInstitution = Institution::factory()->for($this->tenant)->create();
});

test('records a meeting for their own institution only', function (): void {
    $meeting = fn (Institution $institution): array => [
        'start_time' => now()->addDay()->setTime(15, 0)->toDateTimeString(),
        'institution_id' => $institution->id,
    ];

    asUser($this->representative)
        ->post(route('meetings.store'), $meeting($this->ownInstitution))
        ->assertSessionHasNoErrors();

    asUser($this->representative)
        ->post(route('meetings.store'), $meeting($this->otherInstitution))
        ->assertSessionHasErrors('institution_id');

    expect($this->ownInstitution->meetings()->count())->toBe(1)
        ->and($this->otherInstitution->meetings()->count())->toBe(0);
});

test('is offered "Fiksuoti veiklą" on their own institution, not on others, and no wider search', function (): void {
    $recordMeeting = fn (Institution $institution): bool => asUser($this->representative)
        ->get(route('institutions.show', $institution))
        ->viewData('page')['props']['can']['recordMeeting'];

    expect($recordMeeting($this->ownInstitution))->toBeTrue()
        ->and($recordMeeting($this->otherInstitution))->toBeFalse();

    asUser($this->representative)
        ->getJson(route('api.v1.admin.actionWindow.context'))
        ->assertOk()
        ->assertJsonPath('data.institutionSearch.enabled', false);
});

test('opens, edits and fills in their institution\'s meetings but not the rest of the padalinys', function (): void {
    $own = Meeting::factory()->hasAttached($this->ownInstitution)->create(['start_time' => now()->subYears(2)]);
    $other = Meeting::factory()->hasAttached($this->otherInstitution)->create();
    $ownItem = AgendaItem::factory()->for($own)->create();

    asUser($this->representative)->get(route('meetings.show', $own))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('abilities.update', true));
    asUser($this->representative)->get(route('meetings.show', $other))->assertForbidden();

    asUser($this->representative)
        ->patch(route('agendaItems.update', $ownItem), ['title' => ['lt' => 'Stipendijų tvarka']])
        ->assertSessionHasNoErrors();

    asUser($this->representative)->delete(route('meetings.destroy', $other))->assertForbidden();
    asUser($this->representative)->delete(route('meetings.destroy', $own))->assertRedirect();

    expect($own->fresh()->trashed())->toBeTrue();
});

test('edits any problem of their padalinys, not only their own, but none elsewhere', function (): void {
    // Deliberately padalinys-wide for now (problems.update.padalinys), unlike their meetings.
    $colleaguesProblem = Problem::factory()->create(['tenant_id' => $this->tenant->id, 'created_by' => makeUser($this->tenant)->id]);
    $otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $elsewhere = Problem::factory()->create(['tenant_id' => $otherTenant->id]);
    $update = fn (Problem $problem): array => [
        'title' => ['lt' => 'Trūksta vietų bendrabutyje', 'en' => 'Not enough dormitory places'],
        'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        'tenant_id' => $problem->tenant_id,
        'occurred_at' => now()->subDay()->toDateString(),
        'status' => 'open',
    ];

    asUser($this->representative)
        ->patch(route('problems.update', $colleaguesProblem), $update($colleaguesProblem))
        ->assertSessionHasNoErrors();
    asUser($this->representative)
        ->patch(route('problems.update', $elsewhere), $update($elsewhere))
        ->assertForbidden();

    expect($colleaguesProblem->fresh()->getTranslation('title', 'lt'))->toBe('Trūksta vietų bendrabutyje');
});

test('own-scope representative is allowed to update an agenda item from their own institution meeting and denied for another institution', function (): void {
    $ownMeeting = Meeting::factory()->hasAttached($this->ownInstitution)->create();
    $otherMeeting = Meeting::factory()->hasAttached($this->otherInstitution)->create();

    $ownItem = AgendaItem::factory()->for($ownMeeting)->create();
    $otherItem = AgendaItem::factory()->for($otherMeeting)->create();

    asUser($this->representative)
        ->patch(route('agendaItems.update', $ownItem), ['title' => ['lt' => 'Savo klausimas']])
        ->assertSessionHasNoErrors();

    asUser($this->representative)
        ->patch(route('agendaItems.update', $otherItem), ['title' => ['lt' => 'Kito klausimas']])
        ->assertForbidden();

    expect($ownItem->fresh()->getTranslation('title', 'lt'))->toBe('Savo klausimas')
        ->and($otherItem->fresh()->getTranslation('title', 'lt'))->not->toBe('Kito klausimas');
});

