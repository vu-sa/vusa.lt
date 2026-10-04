<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->institution = Institution::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->type = InstitutionType::factory()->create([
        'title' => ['lt' => 'Test InstitutionType', 'en' => 'Test InstitutionType'],
    ]);

    $this->duty = Duty::factory()->create([
        'institution_id' => $this->institution->id,
        'contacts_grouping' => 'none',
    ]);

    // Attach the type to the duty
    $this->duty->types()->attach($this->type->id);

    // Attach a user to the duty
    $this->user = makeUser($this->tenant);
    $this->duty->users()->attach($this->user->id, ['start_date' => now()->subDay()]);
});

describe('contact eager loading with types', function (): void {
    test('eager loading current_users.current_duties.types loads all relations without N+1', function (): void {
        $types = collect([$this->type]);

        // This is the pattern used by ContactController::institutionDutyTypeContacts
        $duties = $this->institution->load(['duties' => function ($query) use ($types): void {
            $query->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))
                ->with('current_users.current_duties.types');
        }])->duties;

        expect($duties)->not->toBeEmpty();

        $duty = $duties->first();
        expect($duty)->not->toBeNull();

        $currentUsers = $duty->current_users;
        expect($currentUsers)->not->toBeEmpty();

        // Verify that the nested relations are loaded (not lazy-loaded)
        $user = $currentUsers->first();
        expect($user->relationLoaded('current_duties'))->toBeTrue();

        if ($user->current_duties->isNotEmpty()) {
            $firstDuty = $user->current_duties->first();
            expect($firstDuty->relationLoaded('types'))->toBeTrue();
        }
    });

    test('types are available for filtering without additional queries', function (): void {
        $types = collect([$this->type]);

        $duties = $this->institution->load(['duties' => function ($query) use ($types): void {
            $query->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))
                ->with('current_users.current_duties.types');
        }])->duties;

        $contacts = $duties->pluck('current_users')->flatten()->unique('id');

        // Filter duties by types - this should NOT trigger additional queries
        // because types are already eager loaded
        $contacts = $contacts->map(function ($contact) use ($types) {
            $contact->filtered_current_duties = $contact->current_duties->filter(fn ($duty) => $duty->types->intersect($types)->count() > 0);

            return $contact;
        });

        expect($contacts)->not->toBeEmpty();
        $contact = $contacts->first();
        expect($contact->filtered_current_duties)->not->toBeEmpty();
    });
});

test('public contacts preserve cross-locale pronouns and the assignment name override', function (string $grouping, string $contactPath): void {
    $this->user->update(['name' => 'Jonas Jonaitis', 'pronouns' => ['lt' => '', 'en' => 'she / her']]);
    $this->duty->update(['name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'], 'contacts_grouping' => $grouping]);
    $this->duty->users()->updateExistingPivot($this->user->id, ['use_original_duty_name' => true]);

    $this->get(route('contacts.institution', [
        'subdomain' => 'www', 'lang' => 'lt', 'contactsString' => 'kontaktai', 'institution' => $this->institution->id,
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contacts/ShowInstitution')
        ->where($contactPath.'.name', 'Jonas Jonaitis')
        ->where($contactPath.'.duty_pronouns.en', 'she / her')
        ->where($contactPath.'.duties.0.pivot.use_original_duty_name', true)
    );
})->with([
    'ungrouped' => ['none', 'contacts.0'],
    'grouped by tenant' => ['tenant', 'contactSections.0.groups.0.contacts.0'],
    'group fallback' => ['study_program', 'contactSections.0.contacts.0'],
]);
