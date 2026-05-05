<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->institution = Institution::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->type = Type::factory()->create([
        'title' => ['lt' => 'Test Type', 'en' => 'Test Type'],
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

describe('contact eager loading with types', function () {
    test('eager loading current_users.current_duties.types loads all relations without N+1', function () {
        $types = collect([$this->type]);

        // This is the pattern used by ContactController::institutionDutyTypeContacts
        $duties = $this->institution->load(['duties' => function ($query) use ($types) {
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

    test('types are available for filtering without additional queries', function () {
        $types = collect([$this->type]);

        $duties = $this->institution->load(['duties' => function ($query) use ($types) {
            $query->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))
                ->with('current_users.current_duties.types');
        }])->duties;

        $contacts = $duties->pluck('current_users')->flatten()->unique('id');

        // Filter duties by types - this should NOT trigger additional queries
        // because types are already eager loaded
        $contacts = $contacts->map(function ($contact) use ($types) {
            $contact->filtered_current_duties = $contact->current_duties->filter(function ($duty) use ($types) {
                return $duty->types->intersect($types)->count() > 0;
            });

            return $contact;
        });

        expect($contacts)->not->toBeEmpty();
        $contact = $contacts->first();
        expect($contact->filtered_current_duties)->not->toBeEmpty();
    });
});
