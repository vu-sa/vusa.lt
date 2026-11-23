<?php

namespace App\Services;

use App\Actions\Responsibilities\AuthorizeDutyAssignment;
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
        unset($attributes['assignment_origin']);
        $attributes['start_date'] ??= $this->newTermStartDate($duty, $userId, $tenantId);

        $create = fn (): Dutiable => Dutiable::create([
            ...$attributes,
            'duty_id' => $duty->id,
            'dutiable_type' => MorphMap::alias(User::class),
            'dutiable_id' => $userId,
            'tenant_id' => $tenantId,
            'via_dutiable_id' => null,
        ]);

        return $audit ? $duty->auditRelationChange('users', $create) : $create();
    }

    /** @param Builder<Dutiable> $query */
    public function endTerms(Builder $query, DateTimeInterface|string|null $endDate = null): void
    {
        $this->endDateDutiables($query, $endDate ?? today()->subDay());
    }

    /** @param list<string> $userIds */
    public function syncRepresentatives(Duty $duty, array $userIds, ?int $tenantId = null, ?User $actor = null): void
    {
        $currentUserIds = $this->manualDutyTerms($duty->id, $tenantId)->current()->distinct()->pluck('dutiable_id')->all();
        $toRemove = array_diff($currentUserIds, $userIds);
        $toAdd = array_values(array_unique(array_diff($userIds, $currentUserIds)));

        if ($actor !== null) {
            foreach ($toAdd as $userId) {
                AuthorizeDutyAssignment::execute($actor, $duty, (string) $userId);
            }
        }

        foreach ($toRemove as $userId) {
            $this->endTerms($this->manualTerms($duty->id, $userId, $tenantId)->current());
        }

        if ($toAdd) {
            $duty->auditRelationChange('users', function () use ($duty, $toAdd, $tenantId): void {
                foreach ($toAdd as $userId) {
                    $this->startTerm($duty, $userId, $tenantId, audit: false);
                }
            });
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
