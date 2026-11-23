<?php

namespace App\Services;

use App\Actions\Responsibilities\AuthorizeDutyAssignment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\Dutiables\DutiablePeriods;
use App\Support\MorphMap;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DutyAssignmentService
{
    /** Called inside a transaction so closure and automatic assignment serialize on the same institutions. */
    public function lockExOfficioContext(Dutiable $source, Duty $target): bool
    {
        $sourceInstitutionId = Duty::withTrashed()->whereKey($source->duty_id)->value('institution_id');
        $institutions = Institution::withTrashed()->whereIn('id', [$sourceInstitutionId, $target->institution_id])
            ->orderBy('id')->lockForUpdate()->get();
        $source->refresh();

        return ! $source->ended_by_institution_deactivation
            && (bool) $institutions->firstWhere('id', $sourceInstitutionId)?->is_active
            && (bool) $institutions->firstWhere('id', $target->institution_id)?->is_active;
    }

    /** @return Builder<Dutiable> */
    public function manualTerms(string $dutyId, string $userId, ?int $tenantId): Builder
    {
        return $this->manualDutyTerms($dutyId, $tenantId)->where('dutiable_id', $userId);
    }

    /** @return Builder<Dutiable> */
    private function manualDutyTerms(string $dutyId, ?int $tenantId): Builder
    {
        $query = Dutiable::query()
            ->where('duty_id', $dutyId)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNull('via_dutiable_id');

        return $tenantId === null ? $query->whereNull('tenant_id') : $query->where('tenant_id', $tenantId);
    }

    /** @param array<string, mixed> $attributes */
    public function startTerm(Duty $duty, string $userId, ?int $tenantId, array $attributes = [], bool $audit = true): Dutiable
    {
        return DB::transaction(function () use ($duty, $userId, $tenantId, $attributes, $audit): Dutiable {
            $this->lockDuty($duty);
            unset($attributes['assignment_origin']);
            $attributes['start_date'] = isset($attributes['start_date'])
                ? Carbon::parse($attributes['start_date'])->toDateString()
                : $this->newTermStartDate($duty, $userId, $tenantId);
            $end = isset($attributes['end_date']) ? Carbon::parse($attributes['end_date'])->toDateString() : null;

            if ($this->manualTerms($duty->id, $userId, $tenantId)
                ->overlapping($attributes['start_date'], $end)->lockForUpdate()->first(['id']) !== null) {
                throw ValidationException::withMessages(['user_id' => __('dutiables.assign.already_assigned')]);
            }

            $create = fn (): Dutiable => Dutiable::create([
                ...$attributes,
                'end_date' => $end,
                'duty_id' => $duty->id,
                'dutiable_type' => MorphMap::alias(User::class),
                'dutiable_id' => $userId,
                'tenant_id' => $tenantId,
                'via_dutiable_id' => null,
            ]);

            return $audit ? $duty->auditRelationChange('current_users', $create) : $create();
        });
    }

    public function lockDuty(Duty $duty): void
    {
        // Follow the institution-first lock order used by closure and duty updates.
        Institution::withTrashed()->whereKey($duty->institution_id)->lockForUpdate()->first();
        Duty::whereKey($duty->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  list<string>  $addedUserIds
     * @param  list<string>  $freedUserIds  Users whose current manual terms will all end before today.
     */
    public function exceedsTenantQuota(Duty $duty, int $tenantId, int $quota, array $addedUserIds, array $freedUserIds = []): bool
    {
        $seats = $duty->dutiables()->current()->where('tenant_id', $tenantId)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->get(['dutiable_id', 'via_dutiable_id']);
        $occupied = $seats->pluck('dutiable_id')->unique()->all();
        $freedUserIds = array_diff($freedUserIds, $seats->whereNotNull('via_dutiable_id')->pluck('dutiable_id')->all());
        $remaining = array_diff($occupied, $freedUserIds);
        $added = array_diff(array_unique($addedUserIds), $remaining);

        return $added !== [] && count($remaining) + count($added) > $quota;
    }

    public function tenantQuota(Duty $duty, int $tenantId): ?int
    {
        $quota = $duty->assignableTenants->find($tenantId)?->getAttribute('pivot')?->quota;

        return $quota === null ? null : (int) $quota;
    }

    public function validateSeatChange(Dutiable $row, ?string $endDate, ?int $tenantId, User $actor, string $attribute): void
    {
        $today = today()->toDateString();

        if (($row->tenant_id === $tenantId && DutiablePeriods::grantsAccess($row))
            || ($endDate ?? $today) < $today
            || $tenantId === null || $row->via_dutiable_id !== null
            || $row->dutiable_type !== MorphMap::alias(User::class)
            || ($duty = $row->duty) === null || $actor->can('update', $duty)) {
            return;
        }

        $quota = $this->tenantQuota($duty, $tenantId);

        if ($quota !== null && $this->exceedsTenantQuota($duty, $tenantId, $quota, [$row->dutiable_id])) {
            throw ValidationException::withMessages([$attribute => __('dutiables.assign.quota_exceeded', ['quota' => $quota])]);
        }
    }

    /**
     * Without a date the last day is yesterday; a term that has not started yet is withdrawn instead of inverted.
     *
     * @param  Builder<Dutiable>  $query
     */
    public function endTerms(Builder $query, DateTimeInterface|string|null $endDate = null): void
    {
        if ($endDate === null) {
            (clone $query)->whereDate('start_date', '>=', today())->get()->each->delete();
            $query->whereDate('start_date', '<', today());
        }

        $this->endDateDutiables($query, $endDate ?? today()->subDay());
    }

    /** @param list<string> $userIds */
    public function syncRepresentatives(Duty $duty, array $userIds, ?int $tenantId = null, ?User $actor = null): void
    {
        DB::transaction(function () use ($duty, $userIds, $tenantId, $actor): void {
            $this->lockDuty($duty);
            $currentUserIds = $this->manualDutyTerms($duty->id, $tenantId)->current()->lockForUpdate()->pluck('dutiable_id')->all();
            $toRemove = array_unique(array_diff($currentUserIds, $userIds));
            $toAdd = array_unique(array_diff($userIds, $currentUserIds));

            if ($actor !== null) {
                foreach ($toAdd as $userId) {
                    AuthorizeDutyAssignment::execute($actor, $duty, (string) $userId);
                }
            }

            $duty->auditRelationChange('current_users', function () use ($duty, $toRemove, $toAdd, $tenantId): void {
                foreach ($toRemove as $userId) {
                    $this->endTerms($this->manualTerms($duty->id, $userId, $tenantId)->current());
                }

                foreach ($toAdd as $userId) {
                    $this->startTerm($duty, $userId, $tenantId, audit: false);
                }
            });
        });
    }

    /**
     * @param  list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>  $items
     */
    public function syncTenantRepresentatives(Duty $duty, array $items, ?User $actor): void
    {
        foreach ($items as $item) {
            if (array_key_exists('user_ids', $item)) {
                $this->syncRepresentatives($duty, $item['user_ids'] ?? [], (int) $item['tenant_id'], $actor);
            }
        }
    }

    private function newTermStartDate(Duty $duty, string $userId, ?int $tenantId): string
    {
        $lastEnd = Dutiable::query()->where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))->where('dutiable_id', $userId)
            ->when($tenantId === null, fn ($query) => $query->whereNull('tenant_id'), fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('end_date')->orderByDesc('end_date')->lockForUpdate()->value('end_date');
        $start = today()->toDateString();

        // The end date is inclusive, so a returning member's new period must start after it (closures included).
        return $lastEnd === null ? $start : max($start, Carbon::parse($lastEnd)->addDay()->toDateString());
    }

    /** @param Builder<Dutiable> $query */
    public function endDateDutiables(Builder $query, DateTimeInterface|string $endDate, bool $institutionDeactivation = false): void
    {
        // Mass updates skip the model events that sync derived seats and invalidate access.
        foreach ($query->get() as $dutiable) {
            if ($institutionDeactivation) {
                $dutiable->forceFill(['ended_by_institution_deactivation' => true]);
            }
            $dutiable->update(['end_date' => $endDate]);
        }
    }

    /**
     * @param  list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>  $items
     * @return array<int, array{quota: int|null}>
     */
    public function buildAssignableTenantsSync(array $items): array
    {
        $sync = [];
        foreach ($items as $item) {
            $sync[$item['tenant_id']] = ['quota' => $item['quota'] ?? null];
        }

        return $sync;
    }
}
