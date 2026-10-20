<?php

namespace App\Actions\Responsibilities;

use App\Models\Role;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Responsibilities a user may grant or take away: all of them for whoever manages responsibilities,
 * otherwise only those they hold themselves, so nobody hands out access they do not have.
 */
class GrantableResponsibilities
{
    /**
     * @return Collection<int, Role>
     */
    public static function for(User $user): Collection
    {
        return self::query($user)->orderBy('name')->get();
    }

    public static function allows(User $user, Role $role): bool
    {
        return self::query($user)->whereKey($role->getKey())->exists();
    }

    /**
     * @return Builder<Role>
     */
    private static function query(User $user): Builder
    {
        $query = Role::query()->exceptSuperAdmin();

        if ($user->isSuperAdmin() || app(ModelAuthorizer::class)->allows($user, 'roles.update.*')) {
            return $query;
        }

        $dutyIds = $user->current_duties()->pluck('duties.id');

        return $query->where(fn (Builder $held) => $held
            ->whereHas('duties', fn (Builder $duties) => $duties->whereIn('duties.id', $dutyIds))
            ->orWhereHas('users', fn (Builder $users) => $users->whereKey($user->getKey())));
    }
}
