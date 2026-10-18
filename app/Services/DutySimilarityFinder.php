<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds existing duties that look like the one an admin is about to create.
 *
 * The dominant cause of duplicate duties in this app is not that admins fail to
 * search — it's that they don't know duty names are inflected automatically per
 * holder's pronouns (see resources/js/Utils/String.ts::changeDutyNameEndings), so
 * they create a second, feminine duty instead of assigning the existing one.
 * `DutyNameNormalizer` collapses exactly that class of variant.
 *
 * Three match tiers, surfaced very differently:
 * - Same institution: a near-certain duplicate. Loud.
 * - Same tenant, other institution: worth a look, so each is named with a link.
 * - Other tenants: expected and common ("Studentų atstovas" legitimately
 *   exists in 50+ institutions) — informational, capped, never alarming.
 *
 * Inactive duties and a duty's former names are matched too: reviving a retired
 * duty keeps its history on one track, where a new one would split it.
 *
 * Advisory only, mirroring UserSimilarityFinder: these are warnings an admin can
 * ignore, never a block.
 */
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
     * A tenant holds a few hundred duties at most, so like the institution tier it is
     * compared in PHP, former names included.
     *
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
                if (DutyNameNormalizer::normalize(self::dutyName($duty)) === $normalized) {
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
     * An institution has, at most, a few dozen duties (33 is the current max in
     * production) — cheap to load in full and compare in PHP rather than push the
     * translatable-JSON comparison into SQL.
     *
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
                $dutyName = self::dutyName($duty);

                if ($dutyName !== '' && mb_strtolower($dutyName) === mb_strtolower($name)) {
                    return ['duty' => $duty, 'reason' => 'same_institution_exact'];
                }

                if ($dutyName !== '' && DutyNameNormalizer::normalize($dutyName) === $normalized) {
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
     * Narrows candidates in SQL before normalising in PHP, the same shape as
     * UserSimilarityFinder::candidates() — a LIKE on the longest word of the typed
     * name, applied to the translatable `name` column via the same driver-aware
     * JSON search TanstackTableService uses for translatable columns.
     *
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

        $this->applyTranslatableLike($query, 'name', $longestWord);

        $matches = $query->limit(self::CANDIDATE_SCAN_LIMIT)->get()
            ->filter(fn (Duty $duty) => DutyNameNormalizer::normalize(self::dutyName($duty)) === $normalized);

        $otherInstitution = $matches->take(self::OTHER_INSTITUTION_LIMIT)
            ->map(fn (Duty $duty) => ['duty' => $duty, 'reason' => 'other_institution'])
            ->values();

        /** @var Collection<int, array{duty: Duty, reason: string}> $otherInstitution */
        return [
            'other_institution' => $otherInstitution,
            'other_institution_count' => $matches->count(),
        ];
    }

    /**
     * Case-insensitive LIKE against a translatable JSON column's `lt` locale,
     * mirroring TanstackTableService::applyJsonSearch (same SQLite/MySQL split —
     * SQLite's json_extract has no JSON_UNQUOTE).
     */
    private function applyTranslatableLike(Builder $query, string $column, string $word): void
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $word);
        $pattern = "%{$escaped}%";

        if (config('database.default') === 'sqlite') {
            $query->whereRaw("LOWER(json_extract({$column}, '$.lt')) LIKE LOWER(?)", [$pattern]);
        } else {
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.lt'))) LIKE LOWER(?)", [$pattern]);
        }
    }

    /** The name duplicates are compared by: Lithuanian, else English. */
    public static function dutyName(Duty $duty): string
    {
        $name = $duty->getTranslation('name', 'lt') ?: $duty->getTranslation('name', 'en');

        return trim((string) $name);
    }
}
