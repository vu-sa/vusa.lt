<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionFollow;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InstitutionSubscriptionService
{
    public function follow(User $user, Institution $institution): InstitutionFollow
    {
        return InstitutionFollow::firstOrCreate([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
        ]);
    }

    public function unfollow(User $user, Institution $institution): bool
    {
        return InstitutionFollow::where([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
        ])->delete() > 0;
    }

    /**
     * Follow several institutions at once; ones already followed are left as they are.
     *
     * @param  Collection<int, Institution>  $institutions
     */
    public function followMany(User $user, Collection $institutions): void
    {
        $now = Carbon::now();

        InstitutionFollow::insertOrIgnore($institutions->map(fn (Institution $institution): array => [
            'id' => strtolower((string) Str::ulid()),
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Unfollow several institutions at once.
     *
     * @param  array<int, string>  $institutionIds
     */
    public function unfollowMany(User $user, array $institutionIds): void
    {
        InstitutionFollow::where('user_id', $user->id)->whereIn('institution_id', $institutionIds)->delete();
    }

    /**
     * Get subscription status for an institution.
     *
     * @return array{is_followed: bool, is_duty_based: bool}
     */
    public function getStatus(User $user, Institution $institution): array
    {
        return [
            'is_followed' => $user->follows($institution),
            'is_duty_based' => $user->hasInstitution($institution),
        ];
    }

    /**
     * Toggle follow state for an institution.
     *
     * @return bool New follow state (true if now following)
     */
    public function toggleFollow(User $user, Institution $institution): bool
    {
        if ($user->follows($institution)) {
            $this->unfollow($user, $institution);

            return false;
        }

        $this->follow($user, $institution);

        return true;
    }
}
