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
     * @return list<array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, institutions: list<string>}>
     */
    public static function execute(User $user): array
    {
        $institutions = $user->authorization_duties
            ->loadMissing('institution')
            ->pluck('institution')
            ->filter(fn ($institution) => $institution instanceof Institution)
            ->unique('id');

        $coordinators = [];

        foreach ($institutions as $institution) {
            // The first a rep would be pointed to, as before.
            $coordinator = GetInstitutionCoordinators::execute([$institution], $user)[0] ?? null;

            if ($coordinator === null) {
                continue;
            }

            $coordinators[$coordinator['id']] ??= [...$coordinator, 'institutions' => []];
            $coordinators[$coordinator['id']]['institutions'][] = (string) $institution->name;
        }

        return array_values($coordinators);
    }
}
