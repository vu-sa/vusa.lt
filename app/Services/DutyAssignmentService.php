<?php

namespace App\Services;

use App\Actions\Responsibilities\AuthorizeDutyAssignment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\Dutiables\DutiablePeriods;
use App\Support\MorphMap;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
    public function startTerm(Duty $duty, string $userId, ?int $tenantId, array $attributes = [], bool $audit = true, string $dateAttribute = 'end_date'): Dutiable
    {
        return DB::transaction(function () use ($duty, $userId, $tenantId, $attributes, $audit, $dateAttribute): Dutiable {
            $this->lockDuty($duty);
            unset($attributes['assignment_origin']);
            $attributes['start_date'] = isset($attributes['start_date'])
                ? Carbon::parse($attributes['start_date'])->toDateString()
                : $this->newTermStartDate($duty, $userId, $tenantId);
            $end = isset($attributes['end_date']) ? Carbon::parse($attributes['end_date'])->toDateString() : null;

            $this->validateDateOrder($attributes['start_date'], $end, $dateAttribute);

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

    /** @return Collection<string, Institution> */
    public function lockDuty(Duty $duty): Collection
    {
        return $this->lockDuties(collect([$duty]));
    }

    /**
     * @param  Collection<int, Duty>  $duties
     * @return Collection<string, Institution>
     */
    public function lockDuties(Collection $duties, array $additionalInstitutionIds = []): Collection
    {
        $dutyIds = $duties->pluck('id')->unique()->all();
        $targetsQuery = Duty::query()->whereHas('exOfficioSourceDuties', fn ($query) => $query->whereKey($dutyIds));
        $targets = $targetsQuery->get(array_values($targetsQuery->qualifyColumns(['id', 'institution_id'])));
        $context = $duties->concat($targets);

        $institutions = Institution::withTrashed()->whereKey($context->pluck('institution_id')->concat($additionalInstitutionIds)->unique()->all())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        Duty::withTrashed()->whereKey($context->pluck('id')->unique()->all())
            ->orderBy('id')->lockForUpdate()->get();

        return $institutions;
    }

    /** @param Collection<int, Duty> $duties */
    public function auditCurrentUsers(Collection $duties, Closure $mutation): mixed
    {
        foreach ($duties->unique('id') as $duty) {
            $write = $mutation;
            $mutation = fn () => $duty->auditRelationChange('current_users', $write);
        }

        return $mutation();
    }

    public function validateTermEdit(Dutiable $row, ?string $startDate, ?string $endDate, ?int $tenantId, string $attribute): void
    {
        $start = $startDate === null ? $row->start_date->toDateString() : Carbon::parse($startDate)->toDateString();
        $end = $endDate === null ? null : Carbon::parse($endDate)->toDateString();
        $this->validateDateOrder($start, $end, $attribute);

        if ($row->via_dutiable_id === null && $row->dutiable_type === MorphMap::alias(User::class)
            && $this->manualTerms($row->duty_id, $row->dutiable_id, $tenantId)
                ->whereKeyNot($row->id)->overlapping($start, $end)->lockForUpdate()->first(['id']) !== null) {
            throw ValidationException::withMessages([$attribute => __('dutiables.assign.already_assigned')]);
        }
    }

    private function validateDateOrder(string $startDate, ?string $endDate, string $attribute): void
    {
        if ($endDate !== null && $endDate < $startDate) {
            throw ValidationException::withMessages([
                $attribute => __('validation.after_or_equal', [
                    'attribute' => __('forms.fields.duty_end_date'),
                    'date' => __('forms.fields.duty_start_date'),
                ]),
            ]);
        }
    }

    /** @return Builder<Dutiable> */
    private function quotaSeats(Duty $duty): Builder
    {
        return $duty->dutiables()->getQuery()->current()
            ->where('dutiable_type', MorphMap::alias(User::class))->whereNotNull('tenant_id');
    }

    /** @return Collection<int, int> */
    public function occupiedTenantSeats(Duty $duty): Collection
    {
        return $this->quotaSeats($duty)->selectRaw('tenant_id, count(distinct dutiable_id) as seats')
            ->groupBy('tenant_id')->pluck('seats', 'tenant_id');
    }

    /**
     * @param  list<string>|null  $addedUserIds  Null counts the committed and pending writes under the duty lock.
     * @param  list<string>  $freedUserIds
     */
    public function exceedsTenantQuota(Duty $duty, int $tenantId, int $quota, ?array $addedUserIds, array $freedUserIds = []): bool
    {
        $query = $this->quotaSeats($duty)->where('tenant_id', $tenantId);
        if ($addedUserIds === null) {
            return $query->lockForUpdate()->pluck('dutiable_id')->unique()->count() > $quota;
        }

        $seats = $query->get(['dutiable_id', 'via_dutiable_id']);
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

    public function enforceTenantQuota(Duty $duty, ?int $tenantId, ?User $actor, string $attribute): void
    {
        if ($tenantId === null || $actor === null || $actor->can('update', $duty)) {
            return;
        }

        $quota = $duty->assignableTenants()->newPivotQuery()->where('tenant_id', $tenantId)->lockForUpdate()->value('quota');
        if ($quota !== null && $this->exceedsTenantQuota($duty, $tenantId, (int) $quota, null)) {
            throw ValidationException::withMessages([$attribute => __('dutiables.assign.quota_exceeded', ['quota' => $quota])]);
        }
    }

    public function allocatesSeat(Dutiable $row, ?string $endDate, ?int $tenantId): bool
    {
        $today = today()->toDateString();

        return $tenantId !== null && $row->via_dutiable_id === null
            && $row->dutiable_type === MorphMap::alias(User::class)
            && ($endDate ?? $today) >= $today
            && ($row->tenant_id !== $tenantId || ! DutiablePeriods::grantsAccess($row));
    }

    /** @param Builder<Dutiable> $query */
    public function endTerms(Builder $query, DateTimeInterface|string|null $endDate = null, string $attribute = 'end_date'): void
    {
        $lastDay = Carbon::parse($endDate ?? today()->subDay())->toDateString();
        $terms = $query->orderBy('id')->lockForUpdate()->get();

        foreach ($terms as $term) {
            if ($term->start_date->toDateString() > $lastDay) {
                $term->delete();
            } elseif ($term->end_date?->toDateString() !== $lastDay) {
                $this->validateTermEdit($term, null, $lastDay, $term->tenant_id, $attribute);
                $term->update(['end_date' => $lastDay]);
            }
        }
    }

    /** @param list<string> $userIds */
    public function syncRepresentatives(Duty $duty, array $userIds, ?int $tenantId = null, ?User $actor = null, string $quotaAttribute = 'user_ids'): void
    {
        DB::transaction(function () use ($duty, $userIds, $tenantId, $actor, $quotaAttribute): void {
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

            if ($toAdd !== []) {
                $this->enforceTenantQuota($duty, $tenantId, $actor, $quotaAttribute);
            }
        });
    }

    /**
     * @param  list<array{tenant_id: int, quota?: int|null, user_ids?: list<string>|null}>  $items
     */
    public function syncTenantRepresentatives(Duty $duty, array $items, ?User $actor): void
    {
        foreach ($items as $index => $item) {
            if (array_key_exists('user_ids', $item)) {
                $this->syncRepresentatives($duty, $item['user_ids'] ?? [], (int) $item['tenant_id'], $actor, "assignable_tenants.$index.user_ids");
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
        DB::transaction(function () use ($query, $endDate, $institutionDeactivation): void {
            $lastDay = Carbon::parse($endDate)->toDateString();
            $terms = $query->get();
            foreach ($terms as $dutiable) {
                $this->validateDateOrder($dutiable->start_date->toDateString(), $lastDay, 'end_date');
                if ($institutionDeactivation) {
                    $dutiable->forceFill(['ended_by_institution_deactivation' => true]);
                }
                $dutiable->update(['end_date' => $lastDay]);
            }
        });
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
