<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

class DutyAssignmentService
{
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
                $userId => ['start_date' => now()->subDay(), 'tenant_id' => $tenantId],
            ])->all());
        }
    }

    /** @param Builder<Dutiable> $query */
    public function endDateDutiables(Builder $query, DateTimeInterface|string $endDate): void
    {
        // Mass updates skip the model events that sync derived seats and invalidate access.
        foreach ($query->get() as $dutiable) {
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
