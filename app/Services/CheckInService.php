<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\User;
use App\States\InstitutionCheckIns\Active;
use App\States\InstitutionCheckIns\AdminSuppressed;
use App\States\InstitutionCheckIns\Disputed;
use App\States\InstitutionCheckIns\Invalidated;
use App\States\InstitutionCheckIns\Expired;
use App\States\InstitutionCheckIns\Withdrawn;
use Illuminate\Support\Carbon;

class CheckInService
{
    /** Create a blackout check-in (mode=blackout). */
    public function create(User $user, Institution $institution, Carbon $until, string $confidence = 'medium', ?string $note = null, string $mode = 'blackout'): InstitutionCheckIn
    {
        // Guards (cap and membership checks should be enforced in FormRequest/Policy)
        $checkIn = new InstitutionCheckIn([
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'tenant_id' => $institution->tenant_id,
            'until_date' => $until,
            'checked_at' => now(),
            'confidence' => $confidence,
            'note' => $note,
            'mode' => $mode,
            'state' => Active::class,
        ]);

        $checkIn->save();

        return $checkIn;
    }

    /** Idempotent verify (handled at controller level with unique constraint). */
    public function confirm(InstitutionCheckIn $checkIn): void
    {
    // No-op: verification count is computed from verifications table.
    }

    public function withdraw(InstitutionCheckIn $checkIn): void
    {
        $checkIn->state->transitionTo(Withdrawn::class);
    }

    public function dispute(InstitutionCheckIn $checkIn, User $by, ?string $reason = null): void
    {
        $checkIn->fill([
            'disputed_by_user_id' => $by->id,
            'disputed_at' => now(),
        ]);
        $checkIn->state->transitionTo(Disputed::class);
        // Optionally record $reason via activity log
    }

    public function resolve(InstitutionCheckIn $checkIn, string $resolution): void
    {
        if ($resolution === 'withdraw') {
            $this->withdraw($checkIn);
            return;
        }
        // else keep Active (optionally update note/date before this)
        $checkIn->state->transitionTo(Active::class);
        $checkIn->fill(['disputed_by_user_id' => null, 'disputed_at' => null])->save();
    }

    public function suppress(InstitutionCheckIn $checkIn, User $admin, string $reason): void
    {
        $checkIn->fill([
            'suppressed_by_user_id' => $admin->id,
            'suppressed_reason' => $reason,
            'suppressed_at' => now(),
        ]);
        $checkIn->state->transitionTo(AdminSuppressed::class);
    }

    public function unsuppress(InstitutionCheckIn $checkIn): void
    {
        $checkIn->state->transitionTo(Active::class);
        $checkIn->fill(['suppressed_by_user_id' => null, 'suppressed_reason' => null, 'suppressed_at' => null])->save();
    }

    /** Invalidate check-ins when a meeting overlaps. */
    public function invalidateByMeeting(Meeting $meeting): void
    {
        $meeting->loadMissing('institutions');
        foreach ($meeting->institutions as $institution) {
            InstitutionCheckIn::query()
                ->where('institution_id', $institution->id)
                ->where('state', Active::class)
                ->whereDate('until_date', '>=', $meeting->start_time->toDateString())
                ->update([
                    'state' => Invalidated::class,
                    'invalidated_by_meeting_id' => $meeting->id,
                ]);
        }
    }

    /** Expire stale active check-ins. */
    public function expireStale(): int
    {
        return InstitutionCheckIn::query()
            ->where('state', Active::class)
            ->whereDate('until_date', '<', now()->toDateString())
            ->update(['state' => Expired::class]);
    }
}
