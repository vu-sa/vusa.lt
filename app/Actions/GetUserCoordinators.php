<?php

namespace App\Actions;

use App\Models\User;

/**
 * "Tavo koordinatoriai" (R-g, O22): the institution managers a rep asks when stuck.
 *
 * A rep seated in institutions of several tenants has one coordinator per tenant, so every
 * distinct one is returned, each with the rep's institutions they cover.
 */
class GetUserCoordinators
{
    /**
     * @return list<array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, institutions: list<string>}>
     */
    public static function execute(User $user): array
    {
        $institutions = $user->current_duties
            ->loadMissing('institution')
            ->pluck('institution')
            ->filter()
            ->unique('id');

        $coordinators = [];

        foreach ($institutions as $institution) {
            $coordinator = GetInstitutionCoordinator::execute($institution, $user);

            if ($coordinator === null) {
                continue;
            }

            $coordinators[$coordinator['id']] ??= [...$coordinator, 'institutions' => []];
            $coordinators[$coordinator['id']]['institutions'][] = (string) $institution->name;
        }

        return array_values($coordinators);
    }
}
