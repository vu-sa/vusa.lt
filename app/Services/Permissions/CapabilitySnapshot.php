<?php

namespace App\Services\Permissions;

use App\Models\User;

/**
 * An immutable, freshly-computed snapshot of the roles a user effectively holds.
 *
 * Used by AccessChangeAnalyzer to compare a user's roles before and after a
 * proposed change. Effective roles are the union of the user's direct roles and
 * the roles carried by their current (non-ended) duties — read straight from the
 * Eloquent relations so an uncommitted mutation is reflected.
 */
final class CapabilitySnapshot
{
    /**
     * @param  array<string, string>  $roles  Role id => display name
     */
    public function __construct(
        public readonly array $roles,
    ) {}

    /**
     * Capture a fresh snapshot of the user's effective roles.
     */
    public static function capture(User $user): self
    {
        $user->loadMissing('roles:id,name', 'current_duties:id', 'current_duties.roles:id,name');

        $roles = [];

        foreach ($user->roles as $role) {
            $roles[$role->id] = $role->name;
        }

        foreach ($user->current_duties as $duty) {
            foreach ($duty->roles as $role) {
                $roles[$role->id] = $role->name;
            }
        }

        return new self($roles);
    }
}
