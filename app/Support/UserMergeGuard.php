<?php

namespace App\Support;

use App\Models\User;
use App\Services\UserSimilarityFinder;

/**
 * Which user records may be merged at all, whoever asks.
 *
 * Admins cannot rename people, so a matching name is the evidence that two records
 * are one person; a record its owner already renamed has lost that evidence.
 */
class UserMergeGuard
{
    /** The translated reason this pair cannot be merged, or null when it can. */
    public static function blockedReason(User $kept, User $source): ?string
    {
        if ($kept->name_was_changed || $source->name_was_changed) {
            $renamed = $source->name_was_changed ? $source : $kept;

            return __('users.merge.name_changed', ['name' => $renamed->name]);
        }

        if (! UserSimilarityFinder::namesMatch((string) $kept->name, (string) $source->name)) {
            return __('users.merge.name_mismatch', ['name' => $source->name]);
        }

        return null;
    }
}
