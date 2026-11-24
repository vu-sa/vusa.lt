<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\User;

/**
 * "Tavo koordinatoriai" (R-g, O22): the people a rep asks when stuck.
 *
 * Each of the rep's institutions may be coordinated by someone different (its padalinys, its
 * type or the institution itself decides), so every distinct coordinator is returned with the
 * rep's institutions they cover. Bodies nobody coordinates (VU SA's own) are left out.
 */
class GetUserCoordinators
{
    /**
     * @return list<array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, pronouns: array<string, string>, use_original_duty_name: bool, institutions: list<string>}>
     */
    public static function execute(User $user): array
    {
        // Queried afresh: ModelAuthorizer caches these duties with a name-less `institution:id,tenant_id`.
        $institutions = Institution::query()->with('types')
            ->whereIn('id', $user->current_or_upcoming_duties->pluck('institution_id')->filter()->unique())
            ->get();

        return GetInstitutionCoordinators::execute($institutions, $user, firstPerInstitution: true);
    }
}
