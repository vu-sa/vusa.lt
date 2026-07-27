<?php

namespace App\Models\Traits;

use App\Contracts\GuardsForceDelete;

/**
 * Shared reason-building for models implementing {@see GuardsForceDelete}.
 *
 * Permanent deletion of these models is refused rather than attempted, for one of two
 * reasons: a restricting foreign key would make it fail anyway (and the trash view
 * would offer a button that always errors), or a cascading foreign key would make it
 * succeed while silently destroying records that have to outlive it — submitted
 * registrations, reported problems, service history.
 */
trait GuardsForceDeleteWhenReferenced
{
    /**
     * Build the refusal message from the blockers that are actually present.
     *
     * Each entry is a translation key mapped to a count; zero-count entries drop out.
     * Keys under `entities.*` reuse the model-name pluralisations already used across
     * the admin, so "3 susitikimai" declines correctly.
     *
     * @param  array<string, int>  $blockers  translation key => count
     */
    protected function forceDeleteReasonFor(array $blockers): ?string
    {
        $present = array_filter($blockers, static fn (int $count): bool => $count > 0);

        if ($present === []) {
            return null;
        }

        $described = [];

        foreach ($present as $key => $count) {
            $described[] = $count.' '.trans_choice($key, $count);
        }

        return __('trash.blocked.generic', [
            'blockers' => implode(', ', $described),
        ]);
    }

    /**
     * Appendable form of the reason, so an admin index can serialize it per row and
     * the table can disable the action before the user clicks it.
     */
    public function getForceDeleteBlockedReasonAttribute(): ?string
    {
        return $this->forceDeleteBlockedReason();
    }

    /**
     * Count a relation, preferring an eager-loaded `{relation}_count` when the caller
     * supplied one via `withCount()`, so an index page does not run a query per row.
     */
    protected function countedRelation(string $relation): int
    {
        $attribute = str($relation)->snake()->toString().'_count';

        return (int) ($this->{$attribute} ?? $this->{$relation}()->count());
    }
}
