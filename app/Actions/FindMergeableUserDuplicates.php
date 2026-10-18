<?php

namespace App\Actions;

use App\Models\User;
use App\Services\UserSimilarityFinder;
use App\Support\UserMergeGuard;

/**
 * Other records that look like the same person, with what the actor may do about
 * each — shown on the user form so a second record is noticed where admins work.
 */
class FindMergeableUserDuplicates
{
    public function __construct(private readonly UserSimilarityFinder $finder) {}

    /**
     * Shaped like UserSearchApiController::similar() so DuplicateUserWarning renders both.
     *
     * @return list<array{id: string, name: string, reason: string, tenants: list<string>, duties_count: int, email_masked: string, can_manage: bool, can_update_identity: bool, email: string|null, keepable_ids: list<string>, blocked_reason: string|null}>
     */
    public function execute(User $actor, User $user): array
    {
        return $this->finder->find((string) $user->name, '')
            ->filter(fn (array $match): bool => ! $match['user']->is($user))
            ->map(function (array $match) use ($actor, $user): array {
                /** @var User $other */
                $other = $match['user'];
                $reason = UserMergeGuard::blockedReason($actor, $user, $other);
                $keepableIds = $reason === null ? $this->keepableIds($actor, $user, $other) : [];

                if ($reason === null && $keepableIds === []) {
                    $reason = __('users.merge.outside_scope');
                }

                return [
                    'id' => (string) $other->id,
                    'name' => (string) $other->name,
                    'reason' => $match['reason'],
                    'tenants' => $other->tenants->pluck('shortname')->unique()->values()->all(),
                    'duties_count' => (int) $other->duties_count,
                    'email_masked' => UserSimilarityFinder::maskEmail($other->email),
                    'can_manage' => $actor->can('update', $other),
                    'can_update_identity' => $actor->can('updateIdentity', $other),
                    // The full address only for someone who may merge — they pick the login among them.
                    'email' => $keepableIds === [] ? null : (string) $other->email,
                    'keepable_ids' => $keepableIds,
                    'blocked_reason' => $reason,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Which of the two records the actor may keep, by the same checks MergeUsersRequest
     * applies: update the kept record, delete the other, which holds no direct role.
     *
     * @return list<string>
     */
    private function keepableIds(User $actor, User $user, User $other): array
    {
        $canKeep = fn (User $kept, User $source): bool => $actor->can('update', $kept)
            && $actor->can('delete', $source)
            && ! $source->roles()->exists()
            && ! $source->permissions()->exists();

        return array_values(array_filter([
            $canKeep($user, $other) ? (string) $user->id : null,
            $canKeep($other, $user) ? (string) $other->id : null,
        ]));
    }
}
