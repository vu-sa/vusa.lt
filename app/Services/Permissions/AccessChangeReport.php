<?php

namespace App\Services\Permissions;

/**
 * Describes which of the acting user's own roles a proposed change would remove.
 *
 * Produced by diffing a "before" and "after" CapabilitySnapshot. A change that
 * removes one or more roles is surfaced to the user for confirmation; the caller
 * (guardSelfLockout) decides whether a given loss is blocking.
 */
final class AccessChangeReport
{
    /**
     * @param  list<string>  $lostRoles  Display names of roles the user would lose
     */
    public function __construct(
        public readonly array $lostRoles,
    ) {}

    public static function diff(CapabilitySnapshot $before, CapabilitySnapshot $after): self
    {
        $lost = [];

        foreach ($before->roles as $id => $name) {
            if (! array_key_exists($id, $after->roles)) {
                $lost[] = $name;
            }
        }

        return new self(array_values(array_unique($lost)));
    }

    /**
     * Whether the change removes any of the user's roles.
     */
    public function isCritical(): bool
    {
        return count($this->lostRoles) > 0;
    }

    public function severity(): string
    {
        return $this->isCritical() ? 'warning' : 'none';
    }

    /**
     * @return array{lostRoles: list<string>, severity: string}
     */
    public function toArray(): array
    {
        return [
            'lostRoles' => $this->lostRoles,
            'severity' => $this->severity(),
        ];
    }
}
