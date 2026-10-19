<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\PublicUrl;
use Illuminate\Database\Eloquent\Collection;

/**
 * Regenerates every institution alias from its Lithuanian name. When several institutions want
 * the same slug, all of them outside the main tenant get their tenant's prefix, so no single one
 * silently wins the bare slug.
 *
 * @phpstan-type AliasChange array{old: string, new: string, tenant: string|null, redirect: bool}
 */
class NormalizeInstitutionAliases
{
    /** Seeded codes that never named anything, so nobody links to them on purpose. */
    private const string PLACEHOLDER_PATTERN = '/^(padalinio-studentu-atstovai(-\d+)?|pkp-\d+|vm-spk-\d+)$/';

    /**
     * @return array<string, AliasChange> keyed by institution id
     */
    public function plan(): array
    {
        $institutions = $this->institutions();
        $taken = array_fill_keys(GenerateInstitutionAlias::reserved(), true);
        $retired = $this->retiredAliases();
        // A trashed row must not take an alias a live one is giving up: that alias redirects.
        $liveAliases = $institutions->reject->trashed()->pluck('alias')->flip()->all();

        $pending = $institutions->reject(function (Institution $institution) use (&$taken): bool {
            if (! GenerateInstitutionAlias::isPinned($institution)) {
                return false;
            }

            $taken[$institution->alias] = true;

            return true;
        });

        $byBase = $pending->groupBy(fn (Institution $institution): string => $this->baseFor($institution));
        $isTaken = function (string $alias, Institution $institution) use (&$taken, $retired, $liveAliases): bool {
            return isset($taken[$alias])
                || (isset($retired[$alias]) && $retired[$alias] !== $institution->getKey())
                || ($institution->trashed() && isset($liveAliases[$alias]));
        };

        /** @var array<array-key, string> $targets */
        $targets = [];

        // Unclashed slugs go first, so a prefixed name never takes a slug another institution wants bare.
        foreach ($byBase as $base => $group) {
            $base = (string) $base;

            if ($group->count() === 1 && ! $isTaken($base, $group->first())) {
                $targets[$group->first()->getKey()] = $base;
                $taken[$base] = true;
            }
        }

        foreach ($byBase as $base => $group) {
            if ($group->count() > 1 || ! isset($targets[$group->first()->getKey()])) {
                $this->assignClashing((string) $base, $group, $targets, $taken, $isTaken);
            }
        }

        return $this->changes($institutions, $targets);
    }

    /**
     * @param  Collection<int, Institution>  $group
     * @param  array<array-key, string>  $targets
     * @param  array<string, bool>  $taken
     * @param  callable(string, Institution): bool  $isTaken
     */
    private function assignClashing(string $base, Collection $group, array &$targets, array &$taken, callable $isTaken): void
    {
        $mainMembers = $group->filter(fn (Institution $institution): bool => $institution->tenant?->isMain() ?? false);

        foreach ($group as $institution) {
            $wantsBare = $mainMembers->count() === 1 && $mainMembers->first()->is($institution);
            $start = $wantsBare ? $base : GenerateInstitutionAlias::prefixed($base, $institution->tenant);

            $alias = GenerateInstitutionAlias::firstFree($start, $institution->tenant, fn (string $candidate): bool => $isTaken($candidate, $institution));

            $targets[$institution->getKey()] = $alias;
            $taken[$alias] = true;
        }
    }

    /**
     * @param  Collection<int, Institution>  $institutions
     * @param  array<array-key, string>  $targets
     * @return array<string, AliasChange>
     */
    private function changes(Collection $institutions, array $targets): array
    {
        $plan = [];
        $holders = $institutions->countBy('alias')->all();
        // An old alias another institution takes over keeps resolving, so it cannot redirect.
        $reused = array_flip($targets);

        foreach ($institutions as $institution) {
            $new = $targets[$institution->getKey()] ?? null;

            if ($new !== null && $new !== $institution->alias) {
                $plan[$institution->getKey()] = [
                    'old' => (string) $institution->alias,
                    'new' => $new,
                    'tenant' => $institution->tenant?->alias,
                    'redirect' => ! isset($reused[$institution->alias]) && $this->earnsRedirect($institution, $holders),
                ];
            }
        }

        return $plan;
    }

    /**
     * Only an address someone could have meant to link to keeps working: not one several
     * institutions shared, a seeded code, one that was never URL-safe, or a trashed row's.
     *
     * @param  array<string, int>  $holders  alias => how many institutions hold it
     */
    private function earnsRedirect(Institution $institution, array $holders): bool
    {
        $alias = (string) $institution->alias;

        return ! $institution->trashed()
            && ($holders[$alias] ?? 0) === 1
            && preg_match(self::PLACEHOLDER_PATTERN, $alias) !== 1
            && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $alias) === 1;
    }

    /**
     * Saves through the model, so search indexes and the activity log follow, and old addresses
     * the plan marks redirect.
     *
     * @param  array<string, AliasChange>|null  $plan
     * @param  (callable(): void)|null  $onEach
     */
    public function apply(?array $plan = null, ?callable $onEach = null): int
    {
        $plan ??= $this->plan();

        if ($plan === []) {
            return 0;
        }

        $institutions = Institution::withTrashed()->whereKey(array_keys($plan))->get()->keyBy(fn (Institution $institution) => $institution->getKey());

        foreach ($plan as $id => $change) {
            $institution = $institutions->get($id);

            if ($institution === null) {
                continue;
            }

            $institution->recordsLegacyUrl = $change['redirect'];
            $institution->alias = $change['new'];
            $institution->save();

            if ($onEach !== null) {
                $onEach();
            }
        }

        return count($plan);
    }

    /** @return Collection<int, Institution> */
    private function institutions(): Collection
    {
        return Institution::withTrashed()
            ->with('tenant')
            ->get()
            ->sortBy(fn (Institution $institution): array => [
                $institution->tenant?->isMain() ? 0 : 1,
                $institution->created_at->getTimestamp(),
                (string) $institution->getKey(),
            ])
            ->values();
    }

    private function baseFor(Institution $institution): string
    {
        $name = $institution->getTranslation('name', 'lt', false) ?: $institution->getTranslation('short_name', 'lt', false);

        if (blank($name)) {
            return 'institucija-'.strtolower(substr((string) $institution->getKey(), -8));
        }

        return GenerateInstitutionAlias::base($name);
    }

    /**
     * Old aliases already redirecting somewhere must not be handed to a different institution.
     *
     * @return array<string, string> alias => owning institution id
     */
    private function retiredAliases(): array
    {
        return PublicUrl::query()
            ->where('urlable_type', (new Institution)->getMorphClass())
            ->get(['urlable_id', 'url'])
            ->mapWithKeys(fn (PublicUrl $row): array => [
                rawurldecode(substr($row->url, strrpos($row->url, '/') + 1)) => (string) $row->urlable_id,
            ])
            ->all();
    }
}
