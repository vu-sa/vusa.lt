<?php

use App\Enums\MeetingType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    // A coordinator with read + update on agenda items within the tenant.
    $this->coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'Auth test meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create([
        'meeting_id' => $this->meeting->id,
    ]);

    // A meeting participant (duty in the meeting's institution) whose duty has
    // NO role/permission — view-only: can see the item but cannot update it.
    $duty = Duty::factory()->for($this->institution)->create();
    $this->viewer = User::factory()->create();
    $this->viewer->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => null]);

    // Someone with no relationship to the meeting at all.
    $this->outsider = makeUser(
        Tenant::query()->where('id', '!=', $this->tenant->id)->inRandomOrder()->first() ?? $this->tenant
    );
});

describe('agenda item view is broader than update', function () {
    test('a coordinator can both view and update', function () {
        expect(Gate::forUser($this->coordinator)->allows('view', $this->agendaItem))->toBeTrue();
        expect(Gate::forUser($this->coordinator)->allows('update', $this->agendaItem))->toBeTrue();
    });

    test('a meeting participant without permission can view but not update', function () {
        expect(Gate::forUser($this->viewer)->allows('view', $this->agendaItem))->toBeTrue();
        expect(Gate::forUser($this->viewer)->allows('update', $this->agendaItem))->toBeFalse();
    });

    test('an outsider can neither view nor update', function () {
        expect(Gate::forUser($this->outsider)->allows('view', $this->agendaItem))->toBeFalse();
        expect(Gate::forUser($this->outsider)->allows('update', $this->agendaItem))->toBeFalse();
    });
});

describe('comment policy follows the parent', function () {
    beforeEach(function () {
        $this->actingAs($this->viewer);
        $this->viewerComment = $this->agendaItem->comment('<p>From the view-only participant</p>');

        $this->actingAs($this->coordinator);
        $this->coordinatorComment = $this->agendaItem->comment('<p>From the coordinator</p>');
    });

    test('anyone who can view the parent can read, react and resolve', function () {
        foreach ([$this->viewer, $this->coordinator] as $user) {
            expect(Gate::forUser($user)->allows('view', $this->coordinatorComment))->toBeTrue();
            expect(Gate::forUser($user)->allows('react', $this->coordinatorComment))->toBeTrue();
            expect(Gate::forUser($user)->allows('resolve', $this->coordinatorComment))->toBeTrue();
        }
    });

    test('an outsider cannot read, react or resolve', function () {
        expect(Gate::forUser($this->outsider)->allows('view', $this->coordinatorComment))->toBeFalse();
        expect(Gate::forUser($this->outsider)->allows('react', $this->coordinatorComment))->toBeFalse();
        expect(Gate::forUser($this->outsider)->allows('resolve', $this->coordinatorComment))->toBeFalse();
    });

    test('only the author can edit their comment', function () {
        expect(Gate::forUser($this->viewer)->allows('update', $this->viewerComment))->toBeTrue();
        expect(Gate::forUser($this->coordinator)->allows('update', $this->viewerComment))->toBeFalse();
    });

    test('the author or a parent-moderator can delete a comment', function () {
        // Author deletes own.
        expect(Gate::forUser($this->viewer)->allows('delete', $this->viewerComment))->toBeTrue();

        // Coordinator (update on parent) moderates the view-only user's comment.
        expect(Gate::forUser($this->coordinator)->allows('delete', $this->viewerComment))->toBeTrue();

        // The view-only participant cannot delete someone else's comment.
        expect(Gate::forUser($this->viewer)->allows('delete', $this->coordinatorComment))->toBeFalse();
    });
});
