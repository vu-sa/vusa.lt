<?php

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

        expect($rows[$this->rows[0]->id]->study_program->name)->toBe('Informatika');
        expect($rows[$this->rows[1]->id]->study_program->name)->toBe('Informatika');
        expect($rows[$this->rows[2]->id]->relationLoaded('study_program'))->toBeTrue()
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
            expect($pivot->comments)->toHaveCount(1);
            expect($pivot->comments->first()->user)->not->toBeNull();
            expect($pivot->approvals)->toHaveCount(1);
        }
    })->with([1, 2]);

    test('the reservation detail includes approval authors and reversion authors', function (): void {
        $pivot = $this->pivots->first();
        $approval = $pivot->approvals()->firstOrFail();
        $approval->update(['reverted_at' => now(), 'reverted_by_id' => $this->user->id]);

        asUser($this->user)->get(route('reservations.show', $this->reservation))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($pivot, $approval): void {
                $resources = collect($page->toArray()['props']['reservation']['resources']);
                $resource = $resources->firstWhere('pivot.id', $pivot->id);
                $history = collect($resource['pivot']['approvals'])->firstWhere('id', $approval->id);

                expect($history['user']['id'])->toBe($this->user->id)
                    ->and($history['reverted_by']['id'])->toBe($this->user->id);
            });
    });
});
