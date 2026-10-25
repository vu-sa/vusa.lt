<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Duplicate suggestions are advisory: matching never blocks creating a duty. */
class DutySimilarityFinder
{
    /** How many other-institution matches to name individually. */
    private const int OTHER_INSTITUTION_LIMIT = 3;

    /** Rows scanned when narrowing other-institution candidates. */
    private const int CANDIDATE_SCAN_LIMIT = 50;

    /**
     * @return array{
     *     same_institution: Collection<int, array{duty: Duty, reason: string}>,
     *     same_tenant: Collection<int, array{duty: Duty, reason: string}>,
     *     other_institution: Collection<int, array{duty: Duty, reason: string}>,
     *     other_institution_count: int,
     * }
     */
    public function find(string $name, ?string $institutionId, ?string $excludeDutyId = null): array
    {
        $name = trim($name);
        $normalized = DutyNameNormalizer::normalize($name);

        if ($normalized === '') {
            return $this->empty();
        }

        $tenantId = $institutionId ? Institution::query()->whereKey($institutionId)->value('tenant_id') : null;
        $tenantId = $tenantId === null ? null : (int) $tenantId;

        return [
            'same_institution' => $this->findWithinInstitution($name, $normalized, $institutionId, $excludeDutyId),
            'same_tenant' => $this->findWithinTenant($normalized, $tenantId, $institutionId, $excludeDutyId),
            ...$this->findAcrossOtherInstitutions($name, $normalized, $institutionId, $tenantId, $excludeDutyId),
        ];
    }

    /**
     * @return array{same_institution: Collection<int, mixed>, same_tenant: Collection<int, mixed>, other_institution: Collection<int, mixed>, other_institution_count: int}
     */
    private function empty(): array
    {
        return [
            'same_institution' => collect(),
            'same_tenant' => collect(),
            'other_institution' => collect(),
            'other_institution_count' => 0,
        ];
    }

    /**
     * @return Collection<int, array{duty: Duty, reason: string}>
     */
    private function findWithinTenant(string $normalized, ?int $tenantId, ?string $institutionId, ?string $excludeDutyId): Collection
    {
        if ($tenantId === null) {
            return collect();
        }

        $matches = Duty::query()
            ->whereHas('institution', fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($institutionId, fn ($query) => $query->where('institution_id', '!=', $institutionId))
            ->when($excludeDutyId, fn ($query) => $query->where('id', '!=', $excludeDutyId))
            ->with(['institution:id,name,tenant_id', 'institution.tenant:id,shortname', 'current_users:id,name', 'formerNames'])
            ->get()
            ->map(function (Duty $duty) use ($normalized) {
                if ($this->matchesCurrentName($duty, $normalized)) {
                    return ['duty' => $duty, 'reason' => 'same_tenant'];
                }

                return $this->matchesFormerName($duty, $normalized)
                    ? ['duty' => $duty, 'reason' => 'former_name']
                    : null;
            })
            ->filter()
            ->take(self::OTHER_INSTITUTION_LIMIT)
            ->values();

        /** @var Collection<int, array{duty: Duty, reason: string}> $matches */
        return $matches;
    }

    private function matchesFormerName(Duty $duty, string $normalized): bool
    {
        return $duty->formerNames->contains(fn (DutyName $former) => collect($former->getTranslations('name'))
            ->contains(fn ($name) => DutyNameNormalizer::normalize(trim((string) $name)) === $normalized));
    }

    /**
     * @return Collection<int, non-empty-string>
     */
    private function currentNames(Duty $duty): Collection
    {
        return collect($duty->getTranslations('name', config('app.locales')))
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values();
    }

    private function matchesCurrentName(Duty $duty, string $normalized): bool
    {
        return $this->currentNames($duty)
            ->contains(fn (string $name) => DutyNameNormalizer::normalize($name) === $normalized);
    }

    /**
     * @return Collection<int, array{duty: Duty, reason: string}>
     */
    private function findWithinInstitution(string $name, string $normalized, ?string $institutionId, ?string $excludeDutyId): Collection
    {
        if (! $institutionId) {
            return collect();
        }

        $matches = Duty::query()
            ->where('institution_id', $institutionId)
            ->when($excludeDutyId, fn ($query) => $query->where('id', '!=', $excludeDutyId))
            ->with(['institution:id,name', 'institution.tenant:id,shortname', 'current_users:id,name', 'formerNames'])
            ->get()
            ->map(function (Duty $duty) use ($name, $normalized) {
                if ($this->currentNames($duty)->contains(fn (string $currentName) => mb_strtolower($currentName) === mb_strtolower($name))) {
                    return ['duty' => $duty, 'reason' => 'same_institution_exact'];
                }

                if ($this->matchesCurrentName($duty, $normalized)) {
                    return ['duty' => $duty, 'reason' => 'same_institution_variant'];
                }

                return $this->matchesFormerName($duty, $normalized)
                    ? ['duty' => $duty, 'reason' => 'former_name']
                    : null;
            })
            ->filter()
            ->values();

        /** @var Collection<int, array{duty: Duty, reason: string}> $matches */
        return $matches;
    }

    /**
     * @return array{other_institution: Collection<int, array{duty: Duty, reason: string}>, other_institution_count: int}
     */
    private function findAcrossOtherInstitutions(string $name, string $normalized, ?string $institutionId, ?int $tenantId, ?string $excludeDutyId): array
    {
        $longestWord = collect(preg_split('/\s+/u', $name) ?: [])
            ->filter(fn (string $word) => mb_strlen($word) >= 3)
            ->sortByDesc(fn (string $word) => mb_strlen($word))
            ->first();

        if ($longestWord === null) {
            return ['other_institution' => collect(), 'other_institution_count' => 0];
        }

        $query = Duty::query()
            ->when($institutionId, fn ($q) => $q->where('institution_id', '!=', $institutionId))
            // The tenant's own duties are already reported, with links, by findWithinTenant().
            ->when($tenantId, fn ($q) => $q->whereHas('institution', fn (Builder $institution) => $institution->where('tenant_id', '!=', $tenantId)))
            ->when($excludeDutyId, fn ($q) => $q->where('id', '!=', $excludeDutyId))
            ->with(['institution:id,name', 'institution.tenant:id,shortname']);

        $matches = $query->whereTranslationLike('name', $longestWord)
            ->limit(self::CANDIDATE_SCAN_LIMIT)->get()
            ->filter(fn (Duty $duty) => $this->matchesCurrentName($duty, $normalized));

        $otherInstitution = $matches->take(self::OTHER_INSTITUTION_LIMIT)
            ->map(fn (Duty $duty) => ['duty' => $duty, 'reason' => 'other_institution'])
            ->values();

        /** @var Collection<int, array{duty: Duty, reason: string}> $otherInstitution */
        return [
            'other_institution' => $otherInstitution,
            'other_institution_count' => $matches->count(),
        ];
    }

    /** The duplicate-grouping service uses a single canonical name. */
    public static function dutyName(Duty $duty): string
    {
        $name = $duty->getTranslation('name', 'lt') ?: $duty->getTranslation('name', 'en');

        return trim((string) $name);
    }
}
