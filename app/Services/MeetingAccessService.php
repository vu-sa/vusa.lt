<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use App\Settings\MeetingSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MeetingAccessService
{
    public function __construct(private readonly InstitutionAccessService $institutions, private readonly ModelAuthorizer $authorizer) {}

    public function hasBaselineAccess(User $user, Meeting $meeting): bool
    {
        return $meeting->hadMemberAtTheTime($user)
            || $this->institutions->getAccessibleInstitutionIds($user)->intersect($meeting->institutions->modelKeys())->isNotEmpty();
    }

    public function allows(User $user, Meeting $meeting, string $resource, string $ability): bool
    {
        $scope = $this->authorizer->scope($user, "{$resource}.{$ability}.padalinys");

        return $this->authorizer->allows($user, "{$resource}.{$ability}.*")
            || $scope->isAllScope
            || $scope->tenantIds()->intersect($meeting->tenants->modelKeys())->isNotEmpty()
            || ($resource === 'meetings' && in_array($ability, ['create', 'update', 'delete'], true)
                && $this->ownInstitutions($user, $ability)->intersect($meeting->institutions->modelKeys())->isNotEmpty());
    }

    /** @return Collection<int, string> */
    private function ownInstitutions(User $user, string $ability): Collection
    {
        return $this->authorizer->duties($user, "meetings.{$ability}.own")->pluck('institution_id')->filter()->unique()->values();
    }

    /** @return Collection<int, array{institution_id: string, start: Carbon, end: Carbon|null}> */
    private function historicalPeriods(User $user): Collection
    {
        $accessible = $this->institutions->getAccessibleInstitutionIds($user);

        return $user->duties()->whereNotNull('duties.institution_id')->whereNotNull('dutiables.start_date')->get()
            ->reject(fn ($duty): bool => $accessible->contains($duty->institution_id))
            ->map(fn ($duty): array => [
                'institution_id' => $duty->institution_id,
                'start' => Carbon::parse($duty->pivot->start_date)->startOfDay(),
                'end' => $duty->pivot->end_date === null ? null : Carbon::parse($duty->pivot->end_date)->addDay()->startOfDay(),
            ])->values();
    }

    /**
     * @param  Builder<Meeting>  $query
     * @return Builder<Meeting>
     */
    public function visibleMeetings(Builder $query, User $user, bool $includePublic = true): Builder
    {
        return $query->where(function (Builder $visible) use ($user, $includePublic): void {
            $this->baselineQuery($visible, $user);
            $visible->orWhere(fn (Builder $granted) => $this->permissionQuery($granted, $user, 'read'));

            if ($includePublic) {
                $types = app(MeetingSettings::class)->getPublicMeetingInstitutionTypeIds();
                $visible->orWhereHas('institutions.types', fn (Builder $typesQuery) => $typesQuery->whereKey($types));
            }
        });
    }

    /** @param Builder<Meeting> $query */
    private function baselineQuery(Builder $query, User $user): void
    {
        $query->whereHas('institutions', fn (Builder $institutions) => $institutions->whereIn('institutions.id', $this->institutions->getAccessibleInstitutionIds($user)));

        foreach ($this->historicalPeriods($user) as $period) {
            $query->orWhere(function (Builder $during) use ($period): void {
                $during->whereHas('institutions', fn (Builder $institutions) => $institutions->whereKey($period['institution_id']))
                    ->where('meetings.start_time', '>=', $period['start']);

                if ($period['end'] !== null) {
                    $during->where('meetings.start_time', '<', $period['end']);
                }
            });
        }
    }

    /** @param Builder<Meeting> $query */
    private function permissionQuery(Builder $query, User $user, string $ability): void
    {
        $scope = $this->authorizer->scope($user, "meetings.{$ability}.padalinys");

        if ($scope->isAllScope || $this->authorizer->allows($user, "meetings.{$ability}.*")) {
            $query->whereRaw('1 = 1');

            return;
        }

        $query->whereHas('institutions', fn (Builder $institutions) => $institutions->whereIn('tenant_id', $scope->tenantIds()));

        if (in_array($ability, ['create', 'update', 'delete'], true)) {
            $query->orWhereHas('institutions', fn (Builder $institutions) => $institutions->whereIn('institutions.id', $this->ownInstitutions($user, $ability)));
        }
    }

    /**
     * @param  Builder<Meeting>  $query
     * @return Builder<Meeting>
     */
    public function trashedMeetings(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visible) use ($user): void {
            $this->permissionQuery($visible, $user, 'read');
            $visible->orWhere(fn (Builder $restorable) => $this->permissionQuery($restorable, $user, 'delete'));
            $visible->orWhere(fn (Builder $deletable) => $this->permissionQuery($deletable, $user, 'forceDelete'));
        });
    }

    public function searchFilter(User $user, string $resource, ?string $publicClause): ?string
    {
        $scope = $this->authorizer->scope($user, "{$resource}.read.padalinys");

        if ($scope->isAllScope || $this->authorizer->allows($user, "{$resource}.read.*")) {
            return 'id:!=__no_access__';
        }

        $clauses = $publicClause === null ? [] : [$publicClause];
        if ($scope->tenantIds()->isNotEmpty()) {
            $clauses[] = 'tenant_ids:=['.$scope->tenantIds()->implode(',').']';
        }

        $institutions = $this->institutions->getAccessibleInstitutionIds($user);
        if ($institutions->isNotEmpty()) {
            $clauses[] = $this->institutionFilter($institutions);
        }

        $dateField = $resource === 'meetings' ? 'start_time' : 'meeting_start_time';
        foreach ($this->historicalPeriods($user) as $period) {
            $during = [$this->institutionFilter(collect([$period['institution_id']])), "{$dateField}:>={$period['start']->timestamp}"];
            if ($period['end'] !== null) {
                $during[] = "{$dateField}:<{$period['end']->timestamp}";
            }
            $clauses[] = '('.implode(' && ', $during).')';
        }

        return match (count($clauses)) {
            0 => null,
            1 => $clauses[0],
            default => '('.implode(' || ', $clauses).')',
        };
    }

    /** @param Collection<int, string> $ids */
    private function institutionFilter(Collection $ids): string
    {
        return 'institution_ids:=['.$ids->map(fn (string $id): string => "`{$id}`")->implode(',').']';
    }
}
