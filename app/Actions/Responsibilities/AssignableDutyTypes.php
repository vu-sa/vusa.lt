<?php

namespace App\Actions\Responsibilities;

use App\Models\DutyType;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AssignableDutyTypes
{
    /** @return Collection<int, DutyType> */
    public static function for(User $user): Collection
    {
        return self::query($user)->get();
    }

    /**
     * @param  list<int>  $current
     * @param  list<int>  $requested
     */
    public static function allowsChanges(User $user, array $current, array $requested): bool
    {
        $changed = array_unique([...array_diff($requested, $current), ...array_diff($current, $requested)]);

        return $changed === [] || self::query($user)->whereKey($changed)->count() === count($changed);
    }

    /** @return Builder<DutyType> */
    private static function query(User $user): Builder
    {
        $query = DutyType::query()->whereDoesntHave('roles', fn (Builder $roles) => $roles
            ->where('name', config('permission.super_admin_role_name')));

        if ($user->isSuperAdmin() || app(ModelAuthorizer::class)->allows($user, 'roles.update.*')) {
            return $query;
        }

        $roleIds = $user->current_duties()->with('roles:id')->get()->flatMap->roles->pluck('id')->unique();

        return $query->whereIn('id', DB::table('role_can_attach_duty_types')
            ->whereIn('role_id', $roleIds)->select('duty_type_id'));
    }
}
