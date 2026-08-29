<?php

use App\Http\Controllers\Admin\CadenceController;
use App\Models\Cadence;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Settings\CadenceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeUser($this->tenant);
    $this->admin->assignRole(config('permission.super_admin_role_name'));
    $this->outsider = makeUser($this->tenant);
});

describe('unauthorized access', function (): void {
    test('a user without the settings role cannot read cadences', function (): void {
        asUser($this->outsider)->get(route('settings.cadences.index'))->assertForbidden();
    });

    test('a user without the settings role cannot create a global cadence', function (): void {
        asUser($this->outsider)->post(route('settings.cadences.store'), [
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ])->assertForbidden();

        expect(Cadence::count())->toBe(0);
    });

    test('a user without the settings role cannot delete a global cadence', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->outsider)->delete(route('settings.cadences.destroy', $cadence))->assertForbidden();

        expect(Cadence::count())->toBe(1);
    });

    test('guests are redirected', function (): void {
        $this->get(route('settings.cadences.index'))->assertRedirect();
    });
});

describe('authorized access', function (): void {
    test('the index lists global rows and institution overrides', function (): void {
        $institution = Institution::factory()->create();
        Cadence::factory()->forYear(2025)->create();
        Cadence::factory()->forYear(2025)->create(['institution_id' => $institution->id]);

        asUser($this->admin)->get(route('settings.cadences.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/EditCadenceSettings')
                ->has('cadences', 2)
                ->where('settings.default_start_month_day', '07-01')
            );
    });

    test('defaults can be updated', function (): void {
        asUser($this->admin)->post(route('settings.cadences.defaults'), [
            'default_start_month_day' => '05-18',
            'default_end_month_day' => '05-17',
        ])->assertRedirect();

        expect(app(CadenceSettings::class)->default_start_month_day)->toBe('05-18');
    });

    test('a malformed month-day is rejected', function (): void {
        asUser($this->admin)->post(route('settings.cadences.defaults'), [
            'default_start_month_day' => '2025-07-01',
            'default_end_month_day' => '06-30',
        ])->assertSessionHasErrors('default_start_month_day');
    });

    test('an end date before the start is rejected', function (): void {
        asUser($this->admin)->post(route('settings.cadences.store'), [
            'start_date' => '2026-06-30',
            'end_date' => '2025-07-01',
        ])->assertSessionHasErrors('end_date');
    });

    /**
     * MySQL treats NULLs as distinct, so the unique index alone would let a second
     * global row claim the start date — the rule in CadenceRequest is what stops it.
     */
    test('two global cadences cannot share a start date', function (): void {
        Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->post(route('settings.cadences.store'), [
            'start_date' => '2025-07-01',
            'end_date' => '2026-07-31',
        ])->assertSessionHasErrors('start_date');

        expect(Cadence::count())->toBe(1);
    });

    test('an institution may reuse a start date the global ladder already uses', function (): void {
        $institution = Institution::factory()->create();
        Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->post(route('settings.cadences.store'), [
            'institution_id' => $institution->id,
            'start_date' => '2025-07-01',
            'end_date' => '2026-05-17',
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(Cadence::count())->toBe(2);
    });

    test('updating a row does not collide with its own start date', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->patch(route('settings.cadences.update', $cadence), [
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ])->assertRedirect()->assertSessionHasNoErrors();
    });

    test('a cadence can be edited', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->patch(route('settings.cadences.update', $cadence), [
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ])->assertRedirect();

        expect($cadence->refresh()->start_date->toDateString())->toBe('2025-05-18');
    });

    test('a cadence can be deleted', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->admin)->delete(route('settings.cadences.destroy', $cadence))->assertRedirect();

        expect(Cadence::count())->toBe(0);
    });

    test('an unknown institution is rejected', function (): void {
        asUser($this->admin)->post(route('settings.cadences.store'), [
            'institution_id' => '01jnotarealinstitution0000',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ])->assertSessionHasErrors('institution_id');
    });
});

describe('the label is derived from the dates', function (): void {
    test('a cross-year term is named by both years', function (): void {
        expect(Cadence::factory()->forYear(2025)->create()->label)->toBe('2025–2026');
    });

    test('a same-year term is named by the one year', function (): void {
        $cadence = Cadence::factory()->create(['start_date' => '2025-01-01', 'end_date' => '2025-12-31']);

        expect($cadence->label)->toBe('2025');
    });
});

/**
 * Overrides belong to the institution, so they authorize against InstitutionPolicy
 * rather than the settings gate — see CadencePolicy.
 */
describe('institution overrides', function (): void {
    beforeEach(function (): void {
        $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->institutionEditor = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('an institution editor may add an override for their own institution', function (): void {
        asUser($this->institutionEditor)->post(route('settings.cadences.store'), [
            'institution_id' => $this->institution->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(Cadence::forInstitution($this->institution->id)->count())->toBe(1);
    });

    test('an institution editor may delete an override for their own institution', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $this->institution->id]);

        asUser($this->institutionEditor)->delete(route('settings.cadences.destroy', $cadence))->assertRedirect();

        expect(Cadence::count())->toBe(0);
    });

    test('an institution editor cannot touch another tenant\'s institution', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $foreign = Institution::factory()->create(['tenant_id' => $otherTenant->id]);

        asUser($this->institutionEditor)->post(route('settings.cadences.store'), [
            'institution_id' => $foreign->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ])->assertForbidden();

        expect(Cadence::count())->toBe(0);
    });

    test('a user who cannot edit the institution cannot add an override for it', function (): void {
        asUser($this->outsider)->post(route('settings.cadences.store'), [
            'institution_id' => $this->institution->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ])->assertForbidden();

        expect(Cadence::count())->toBe(0);
    });

    test('an institution editor cannot touch the global ladder', function (): void {
        $cadence = Cadence::factory()->forYear(2025)->create();

        asUser($this->institutionEditor)->patch(route('settings.cadences.update', $cadence), [
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ])->assertForbidden();
    });
});

/**
 * Several bodies open and close a term at a named sitting rather than on a date somebody
 * types in. The anchor stores where the boundary came from; the date stays the stored,
 * authoritative value so nothing that reads a cadence has to know about anchors.
 */
describe('meeting anchors', function (): void {
    beforeEach(function (): void {
        $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->conference = Meeting::factory()->create(['start_time' => '2025-05-18 10:00:00']);
        $this->conference->institutions()->attach($this->institution->id);
    });

    test('anchoring a boundary takes its date from the sitting', function (): void {
        asUser($this->admin)->post(route('settings.cadences.store'), [
            'institution_id' => $this->institution->id,
            'start_meeting_id' => $this->conference->id,
            // Deliberately wrong: the anchor is the source, not the posted value.
            'start_date' => '2001-01-01',
            'end_date' => '2026-05-17',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cadence = Cadence::query()->firstOrFail();

        expect($cadence->start_meeting_id)->toBe($this->conference->id)
            ->and($cadence->start_date->toDateString())->toBe('2025-05-18');
    });

    test('moving the sitting moves the term with it', function (): void {
        $cadence = Cadence::factory()->create([
            'institution_id' => $this->institution->id,
            'start_meeting_id' => $this->conference->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ]);

        $this->conference->update(['start_time' => '2025-06-02 10:00:00']);

        expect($cadence->refresh()->start_date->toDateString())->toBe('2025-06-02');
    });

    test('a term with no anchor is left alone when any meeting moves', function (): void {
        $cadence = Cadence::factory()->create([
            'institution_id' => $this->institution->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ]);

        $this->conference->update(['start_time' => '2025-06-02 10:00:00']);

        expect($cadence->refresh()->start_date->toDateString())->toBe('2025-05-18');
    });

    // A faculty term routinely opens at the tenant conference, which is another body's sitting.
    test('a sitting of another institution may open a term', function (): void {
        $foreign = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
        $foreignMeeting = Meeting::factory()->create(['start_time' => '2025-05-18 10:00:00']);
        $foreignMeeting->institutions()->attach($foreign->id);

        asUser($this->admin)->post(route('settings.cadences.store'), [
            'institution_id' => $this->institution->id,
            'start_meeting_id' => $foreignMeeting->id,
            'start_date' => '2001-01-01',
            'end_date' => '2026-05-17',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cadence = Cadence::query()->firstOrFail();

        expect($cadence->start_meeting_id)->toBe($foreignMeeting->id)
            ->and($cadence->start_date->toDateString())->toBe('2025-05-18');
    });

    // The picker only ever offers what the user's scoped search key returns; the rule is what
    // stops a crafted id reaching a sitting they were never shown.
    test('a sitting the editor cannot see is refused', function (): void {
        $editor = makeTenantUser('Communication Coordinator', $this->tenant);

        $otherTenant = Tenant::factory()->create();
        $hidden = Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $hiddenMeeting = Meeting::factory()->create(['start_time' => '2025-05-18 10:00:00']);
        $hiddenMeeting->institutions()->attach($hidden->id);

        asUser($editor)->post(route('settings.cadences.store'), [
            'institution_id' => $this->institution->id,
            'start_meeting_id' => $hiddenMeeting->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ])->assertSessionHasErrors('start_meeting_id');

        expect(Cadence::count())->toBe(0);
    });

    test('the global ladder anchors to nothing — it belongs to no institution', function (): void {
        asUser($this->admin)->post(route('settings.cadences.store'), [
            'start_meeting_id' => $this->conference->id,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ])->assertSessionHasErrors('start_meeting_id');
    });

    test('the institution form is told which sitting a boundary came from', function (): void {
        Cadence::factory()->create([
            'institution_id' => $this->institution->id,
            'start_meeting_id' => $this->conference->id,
            'start_date' => '2025-05-18',
            'end_date' => '2026-05-17',
        ]);

        $payload = CadenceController::payload($this->institution->id);

        expect($payload[0]['start_meeting']['id'])->toBe($this->conference->id)
            ->and($payload[0]['start_meeting']['institution_id'])->toBe($this->institution->id)
            ->and($payload[0]['end_meeting'])->toBeNull();
    });
});
