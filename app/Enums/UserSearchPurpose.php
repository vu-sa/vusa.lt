<?php

namespace App\Enums;

enum UserSearchPurpose: string
{
    case Problem = 'problem';
    case DutyMember = 'duty-member';
    case Involve = 'involve';

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Problem => ['problems.create.padalinys', 'problems.update.padalinys'],
            self::DutyMember => ['duties.update.padalinys'],
            self::Involve => [],
        };
    }

    public function searchesAllTenants(): bool
    {
        return $this !== self::Problem;
    }

    /** Matching on email would let an `involve` search reveal whose address it is. */
    public function usesEmail(): bool
    {
        return $this !== self::Involve;
    }
}
