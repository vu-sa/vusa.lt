<?php

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->institution = $this->admin->duties()->first()->institution;
});

describe('unauthorized access', function (): void {
    test('cannot merge without users update + delete permissions', function (): void {
        $outsider = makeUser($this->tenant);
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        $response = asUser($outsider)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ]);

        expect($response->status())->toBe(403);
        expect(User::find($merged->id))->not->toBeNull();
    });
});

describe('merging dutiables', function (): void {
    test('repoints the merged user assignments onto the kept user and soft-deletes the source', function (): void {
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        $mergedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        Dutiable::factory()->forDuty($mergedDuty)->forUser($merged)->create([
            'start_date' => '2020-01-01',
            'end_date' => '2020-12-31',
        ]);
        Dutiable::factory()->forDuty($mergedDuty)->forUser($merged)->create([
            'start_date' => '2025-01-01',
            'end_date' => null,
        ]);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ])->assertRedirect();

        expect(Dutiable::where('dutiable_id', $merged->id)->count())->toBe(0)
            ->and(Dutiable::where('dutiable_id', $kept->id)->where('duty_id', $mergedDuty->id)->count())->toBe(2)
            ->and(User::withTrashed()->find($merged->id))->not->toBeNull()
            ->and(User::find($merged->id))->toBeNull();
    });

    test('collapses overlapping assignments onto the kept user when both shared a duty', function (): void {
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        $sharedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        // Kept user holds the duty, open-ended.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create([
            'start_date' => '2024-01-01',
            'end_date' => null,
            'additional_email' => null,
        ]);

        // Merged user holds the same duty, overlapping, carrying an email the
        // survivor is missing.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->create([
            'start_date' => '2024-06-01',
            'end_date' => '2024-12-01',
            'additional_email' => 'kept@vusa.lt',
        ]);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ]);

        $rows = Dutiable::where('duty_id', $sharedDuty->id)->where('dutiable_id', $kept->id)->get();

        expect($rows)->toHaveCount(1);
        $survivor = $rows->first();
        expect($survivor->start_date->toDateString())->toBe('2024-01-01')
            ->and($survivor->end_date)->toBeNull()
            ->and($survivor->additional_email)->toBe('kept@vusa.lt');
    });

    test('does not collapse two genuinely separate stints on the same duty', function (): void {
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        $sharedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create([
            'start_date' => '2020-01-01',
            'end_date' => '2020-12-31',
        ]);
        Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->create([
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
        ]);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ]);

        expect(Dutiable::where('duty_id', $sharedDuty->id)->where('dutiable_id', $kept->id)->count())->toBe(2);
    });

    test('keeps owning-tenant and cross-tenant rows for the same duty as separate assignments', function (): void {
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        $sharedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        // Kept holds the duty as a regular (owning-tenant) member.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create([
            'start_date' => '2024-01-01',
            'end_date' => null,
            'tenant_id' => null,
        ]);

        // Merged holds the same duty as a cross-tenant rep — a distinct scope
        // that must survive the merge rather than being folded into the owning row.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->forTenant($this->tenant)->create([
            'start_date' => '2024-06-01',
            'end_date' => null,
        ]);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ]);

        $rows = Dutiable::where('duty_id', $sharedDuty->id)
            ->where('dutiable_id', $kept->id)
            ->orderBy('tenant_id')
            ->get();

        expect($rows)->toHaveCount(2)
            ->and($rows->pluck('tenant_id')->unique()->values()->all())->toBe([null, $this->tenant->id]);
    });
});

describe('transferring related records', function (): void {
    test('repoints reservations onto the kept user, dropping a duplicate the kept user already holds', function (): void {
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        $sharedReservation = Reservation::factory()->create();
        $onlyMerged = Reservation::factory()->create();

        // The kept user already holds one of them — that pivot row must survive
        // (not duplicate), and the merged-only one moves across.
        $kept->reservations()->attach($sharedReservation->id);
        $merged->reservations()->attach([$sharedReservation->id, $onlyMerged->id]);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ]);

        $keptReservationIds = DB::table('reservation_user')->where('user_id', $kept->id)->pluck('reservation_id')->all();

        expect($keptReservationIds)->toHaveCount(2)
            ->and($keptReservationIds)->toContain($sharedReservation->id, $onlyMerged->id)
            ->and(DB::table('reservation_user')->where('user_id', $merged->id)->count())->toBe(0);
    });
});

describe('after merging', function (): void {
    test('the merged user is soft-deleted and can be restored', function (): void {
        $kept = makeUser($this->tenant);
        $merged = makeUser($this->tenant);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $merged->id,
        ]);

        expect(User::find($merged->id))->toBeNull()
            ->and(User::withTrashed()->find($merged->id))->not->toBeNull();

        User::withTrashed()->find($merged->id)->restore();

        expect(User::find($merged->id))->not->toBeNull();
    });

    test('rejects merging a user with themselves', function (): void {
        $kept = makeUser($this->tenant);

        asUser($this->admin)->post(route('users.mergeUsers'), [
            'kept_user_id' => $kept->id,
            'merged_user_id' => $kept->id,
        ])->assertSessionHasErrors('merged_user_id');
    });
});
