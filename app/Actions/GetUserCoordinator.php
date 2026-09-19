<?php

namespace App\Actions;

use App\Models\User;

/**
 * "Tavo koordinatorius" (R-g, O22): the institution manager a rep asks when stuck.
 *
 * No new model — managers are the users holding the settings' manager role in the tenant of
 * one of the user's current institutions.
 */
class GetUserCoordinator
{
    /**
     * @return array{name: string, email: string|null, profile_photo_path: string|null, duty: string|null}|null
     */
    public static function execute(User $user): ?array
    {
        $institutions = $user->current_duties
            ->loadMissing('institution')
            ->pluck('institution')
            ->filter()
            ->unique('id');

        foreach ($institutions as $institution) {
            $manager = GetInstitutionManagers::execute($institution)
                ->first(fn (User $candidate): bool => $candidate->id !== $user->id);

            if ($manager === null) {
                continue;
            }

            return [
                'name' => $manager->name,
                'email' => $manager->email,
                'profile_photo_path' => $manager->profile_photo_path,
                'duty' => $manager->current_duties->first()?->name,
            ];
        }

        return null;
    }
}
