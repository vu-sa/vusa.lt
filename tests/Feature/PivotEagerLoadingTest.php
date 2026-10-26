<?php

use App\Actions\LoadReservationResourceApprovals;
use App\Models\Approval;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

describe('dutiable query loading', function (): void {
    beforeEach(function (): void {
        $tenant = Tenant::query()->first();
        $this->program = StudyProgram::factory()->forTenant($tenant)->create([
            'name' => ['lt' => 'Informatika', 'en' => 'Computer Science'],
        ]);
        $user = User::factory()->create();
        $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
        $this->rows = Dutiable::factory()->forUser($user)->forDuty($duty)->count(3)
            ->sequence(
                ['study_program_id' => $this->program->id],
                ['study_program_id' => $this->program->id],
                ['study_program_id' => null],
            )
            ->create();
    });

    test('retrieves assignments without querying study programs', function (): void {
        $this->expectsDatabaseQueryCount(1);

        $rows = Dutiable::query()->whereKey($this->rows->modelKeys())->get();

        expect($rows)->toHaveCount(3);
        foreach ($rows as $row) {
            expect($row->relationLoaded('study_program'))->toBeFalse()
                ->and($row->toArray())->not->toHaveKey('study_program');
        }
    });

    test('explicitly loads programs in one batch including assignments without a program', function (): void {
        $this->expectsDatabaseQueryCount(2);

        $rows = Dutiable::query()->whereKey($this->rows->modelKeys())
            ->with('study_program:id,name')->get()->keyBy('id');

        expect($rows[$this->rows[0]->id]->study_program->name)->toBe('Informatika')
            ->and($rows[$this->rows[1]->id]->study_program->name)->toBe('Informatika')
            ->and($rows[$this->rows[2]->id]->relationLoaded('study_program'))->toBeTrue()
            ->and($rows[$this->rows[2]->id]->study_program)->toBeNull();
    });
});

describe('reservation resource query loading', function (): void {
    beforeEach(function (): void {
        $tenant = Tenant::query()->first();
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($user);
        $this->user = $user;
        $this->reservation = $reservation;
        $resources = Resource::factory()->for($tenant)->count(2)->create();
        $reservation->resources()->attach($resources->modelKeys(), [
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'quantity' => 1,
            'state' => 'created',
        ]);
        $this->pivots = ReservationResource::query()->where('reservation_id', $reservation->id)->get();
        foreach ($this->pivots as $pivot) {
            Comment::factory()->for($pivot, 'commentable')->for($user)->create();
            Approval::factory()->for($pivot, 'approvable')->for($user)->approved()->create();
        }
    });

    test('retrieves reservation resources without querying comments or approvals', function (): void {
        $this->expectsDatabaseQueryCount(1);

        $pivots = ReservationResource::query()->whereKey($this->pivots->modelKeys())->get();

        expect($pivots)->toHaveCount(2);
        foreach ($pivots as $pivot) {
            expect($pivot->relationLoaded('comments'))->toBeFalse()
                ->and($pivot->relationLoaded('approvals'))->toBeFalse()
                ->and($pivot->toArray())->not->toHaveKeys(['comments', 'approvals']);
        }
    });

    test('explicitly loads comments and approvals in batches', function (int $count): void {
        $this->expectsDatabaseQueryCount(4);

        $pivots = ReservationResource::query()->whereKey($this->pivots->take($count)->modelKeys())
            ->with(['comments', 'approvals'])->get();

        expect($pivots)->toHaveCount($count);
        foreach ($pivots as $pivot) {
            expect($pivot->comments)->toHaveCount(1)
                ->and($pivot->comments->first()->user)->not->toBeNull()
                ->and($pivot->approvals)->toHaveCount(1);
        }
    })->with([1, 2]);

    test('loads approval histories onto existing resource pivots in one query', function (int $count): void {
        $reservation = $this->reservation->fresh();
        $reservation->load(['resources' => fn ($query) => $query->limit($count)]);

        $this->expectsDatabaseQueryCount(1);

        LoadReservationResourceApprovals::execute([$reservation], ['approvals']);

        expect($reservation->resources)->toHaveCount($count);
        foreach ($reservation->resources as $resource) {
            expect($resource->pivot->approvals)->toHaveCount(1)
                ->and($resource->pivot->approvals->first()->approvable_id)->toBe((string) $resource->pivot->id);
        }
    })->with([1, 2]);

    test('keeps separate approval histories when reservations share a resource', function (): void {
        $firstPivot = $this->pivots->first();
        $otherReservation = Reservation::factory()->create();
        $otherReservation->resources()->attach($firstPivot->resource_id, [
            'quantity' => 1,
            'state' => 'created',
        ]);
        $otherPivot = ReservationResource::query()->where('reservation_id', $otherReservation->id)->firstOrFail();
        $otherApproval = Approval::factory()->for($otherPivot, 'approvable')->for($this->user)->rejected()->create();
        $reservations = Reservation::query()->whereKey([$this->reservation->id, $otherReservation->id])
            ->with('resources')->get()->keyBy('id');

        $this->expectsDatabaseQueryCount(1);

        LoadReservationResourceApprovals::execute($reservations, ['approvals']);

        $firstResource = $reservations[$this->reservation->id]->resources->firstWhere('id', $firstPivot->resource_id);
        $otherResource = $reservations[$otherReservation->id]->resources->first();
        expect($firstResource->pivot->approvals)->toHaveCount(1)
            ->and($firstResource->pivot->approvals->first()->approvable_id)->toBe((string) $firstPivot->id)
            ->and($otherResource->pivot->approvals)->toHaveCount(1)
            ->and($otherResource->pivot->approvals->first()->id)->toBe($otherApproval->id);
    });

    test('loads no approval queries for empty reservation batches or reservations without resources', function (): void {
        $reservation = Reservation::factory()->create()->load('resources');

        $this->expectsDatabaseQueryCount(0);

        LoadReservationResourceApprovals::execute([], ['approvals']);
        LoadReservationResourceApprovals::execute([$reservation], ['approvals']);
    });

    test('the reservation detail includes approval authors and reversion authors', function (): void {
        $pivot = $this->pivots->first();
        $approval = $pivot->approvals()->firstOrFail();
        $reverter = User::factory()->create();
        $approval->update([
            'notes' => 'Approved before the schedule changed',
            'reverted_at' => now(),
            'reverted_by_id' => $reverter->id,
            'reversion_notes' => 'The schedule changed',
        ]);

        asUser($this->user)->get(route('reservations.show', $this->reservation))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($pivot, $approval, $reverter): void {
                $resources = collect($page->toArray()['props']['reservation']['resources']);
                $resource = $resources->firstWhere('pivot.id', $pivot->id);
                $history = collect($resource['pivot']['approvals'])->firstWhere('id', $approval->id);

                expect($history['user']['id'])->toBe($this->user->id)
                    ->and($history['reverted_by']['id'])->toBe($reverter->id)
                    ->and($history['notes'])->toBe('Approved before the schedule changed')
                    ->and($history['reversion_notes'])->toBe('The schedule changed');
            });
    });
});
