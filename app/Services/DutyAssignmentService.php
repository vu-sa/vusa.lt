<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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

    /** @param list<string> $userIds */
    public function syncRepresentatives(Duty $duty, array $userIds, ?int $tenantId = null): void
    {
        // Derived seats follow their source duty, never a manual representative list.
        $query = Dutiable::query()
            ->where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('tenant_id', $tenantId)
            ->whereNull('via_dutiable_id')
            ->current();

        $currentUserIds = (clone $query)->pluck('dutiable_id')->all();
        $toRemove = array_values(array_diff($currentUserIds, $userIds));
        $toAdd = array_values(array_unique(array_diff($userIds, $currentUserIds)));

        if ($toRemove) {
            $this->endDateDutiables($query->whereIn('dutiable_id', $toRemove), now()->subDay());
        }

        if ($toAdd) {
            $duty->attachAudited('users', collect($toAdd)->mapWithKeys(fn ($userId) => [
                $userId => ['start_date' => $this->newTermStartDate($duty, $userId, $tenantId), 'tenant_id' => $tenantId],
            ])->all());
        }
    }

    private function newTermStartDate(Duty $duty, string $userId, ?int $tenantId): string
    {
        $lastClosure = Dutiable::query()->where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))->where('dutiable_id', $userId)
            ->where('tenant_id', $tenantId)->where('ended_by_institution_deactivation', true)->max('end_date');
        $start = today()->subDay()->toDateString();

        // The end date is inclusive, so a returning member's new period must start after it.
        return is_string($lastClosure) ? max($start, Carbon::parse($lastClosure)->addDay()->toDateString()) : $start;
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
