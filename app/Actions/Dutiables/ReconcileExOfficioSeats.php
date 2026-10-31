<?php

namespace App\Actions\Dutiables;

use App\Actions\ResolveExOfficioTenantId;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\Dutiables\ExOfficioSeatDates;
use App\Support\MorphMap;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Writes derived seats inside the triggering transaction so permission checks see their final state. */
class ReconcileExOfficioSeats
{
    private static int $pauseDepth = 0;

    public static function isPaused(): bool
    {
        return self::$pauseDepth > 0;
    }

    public static function paused(Closure $callback): mixed
    {
        self::$pauseDepth++;
        try {
            return $callback();
        } finally {
            self::$pauseDepth--;
        }
    }

    public static function forSource(Dutiable $source, bool $includeHistory = false, ?string $targetId = null): array
    {
        if (self::isPaused() || ! self::isRootSource($source)) {
            return [];
        }

        return DB::transaction(fn () => self::reconcileSource($source, $includeHistory, $targetId));
    }

    private static function isRootSource(Dutiable $source): bool
    {
        return $source->via_dutiable_id === null && $source->dutiable_type === MorphMap::alias(User::class)
            && ! $source->ended_by_institution_deactivation;
    }

    private static function reconcileSource(Dutiable $source, bool $includeHistory, ?string $targetId): array
    {
        $source = $source->fresh();
        if ($source === null || ! self::isRootSource($source) || $source->duty === null) {
            return [];
        }
        // Rules are locked first: a term with no seats yet has no row to lock against a concurrent create.
        $targets = $source->duty->exOfficioTargetDuties()->with('assignableTenants')->orderBy('duties.id')->lockForUpdate()->get();
        $seats = $source->derivedDutiables()->with('media')->orderBy('id')->lockForUpdate()->get();
        $findings = [];
        foreach ($targets as $target) {
            if (($targetId === null || $targetId === $target->id)
                && app(DutyAssignmentService::class)->lockExOfficioContext($source, $target)) {
                array_push($findings, ...self::reconcileTarget($source, $target, $seats, $includeHistory));
            }
        }

        return $findings;
    }

    private static function reconcileTarget(Dutiable $source, Duty $target, Collection $seats, bool $includeHistory): array
    {
        $today = today()->toDateString();
        ['dates' => $dates, 'seat' => $seat, 'duplicates' => $duplicates] = ExOfficioSeatDates::forTarget($source, $target->id, $seats, $today);
        $tenantId = ResolveExOfficioTenantId::execute($source, $target);
        if ($dates !== null && $seat !== null) {
            $seat->fill([...$dates, 'tenant_id' => $tenantId]);
            if ($seat->isDirty()) {
                $seat->save();
            }
        } elseif ($dates !== null && ($includeHistory || $dates['end_date'] === null || $dates['end_date'] >= $today)) {
            Dutiable::create([...$dates, 'duty_id' => $target->id, 'dutiable_id' => $source->dutiable_id,
                'dutiable_type' => $source->dutiable_type, 'via_dutiable_id' => $source->id,
                'assignment_origin' => 'ex_officio', 'tenant_id' => $tenantId]);
        }
        $findings = [];
        foreach ($duplicates as $duplicate) {
            if (self::canDeleteDuplicate($duplicate, $seat)) {
                $duplicate->delete();
            } else {
                $findings[] = ['kind' => 'duplicate', 'row_id' => $duplicate->id];
            }
        }

        return $findings;
    }

    private static function canDeleteDuplicate(Dutiable $seat, Dutiable $kept): bool
    {
        $fields = ['tenant_id', 'study_program_id', 'study_program_note', 'additional_email', 'description',
            'additional_photo', 'additional_photo_focal_point', 'use_original_duty_name', 'assignment_origin'];
        $same = collect($fields)->every(fn (string $field) => $seat->getRawOriginal($field) === $kept->getRawOriginal($field));

        return $same && $seat->relationLoaded('media') && $seat->media->isEmpty();
    }

    public static function forDuty(Duty $duty): void
    {
        if (self::isPaused()) {
            return;
        }
        DB::transaction(function () use ($duty): void {
            $sourceIds = $duty->exOfficioSourceDuties()->pluck('duties.id')->push($duty->id)->unique();
            Dutiable::query()->whereIn('duty_id', $sourceIds)->whereNull('via_dutiable_id')
                ->where('dutiable_type', MorphMap::alias(User::class))->current()->orderBy('id')
                ->each(fn (Dutiable $source) => self::forSource($source));
        });
    }

    public static function forInstitution(Institution $institution): void
    {
        if (self::isPaused()) {
            return;
        }
        DB::transaction(fn () => $institution->duties()->orderBy('id')->each(fn (Duty $duty) => self::forDuty($duty)));
    }
}
