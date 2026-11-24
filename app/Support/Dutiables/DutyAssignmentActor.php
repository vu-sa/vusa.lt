<?php

namespace App\Support\Dutiables;

use App\Models\User;
use Closure;

final class DutyAssignmentActor
{
    private static User|false|null $actor = null;

    public static function current(): ?User
    {
        return self::$actor === false ? null : (self::$actor ?? auth()->user());
    }

    public static function scoped(): User|false|null
    {
        return self::$actor;
    }

    /** A null actor keeps the enclosing scope or the authenticated fallback. */
    public static function as(?User $actor, Closure $write): mixed
    {
        return $actor === null ? $write() : self::within($actor, $write);
    }

    public static function system(Closure $write): mixed
    {
        return self::within(false, $write);
    }

    private static function within(User|false $actor, Closure $write): mixed
    {
        $previous = self::$actor;
        self::$actor = $actor;

        try {
            return $write();
        } finally {
            self::$actor = $previous;
        }
    }
}
