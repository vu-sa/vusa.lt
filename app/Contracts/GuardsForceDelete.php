<?php

namespace App\Contracts;

use App\Http\Traits\HandlesSoftDeletes;

/**
 * Implemented by soft-deletable models whose rows may be referenced by records
 * that must outlive them, making permanent deletion impossible.
 *
 * {@see HandlesSoftDeletes::forceDeleteModel()} consults this
 * before deleting, so the user gets an explanation instead of a foreign-key
 * violation. Admin index controllers can serialize the same reason per row to
 * disable the action in the table up front.
 */
interface GuardsForceDelete
{
    /**
     * Translated reason permanent deletion is refused, or null when it may proceed.
     */
    public function forceDeleteBlockedReason(): ?string;
}
