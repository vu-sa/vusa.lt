<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\User;

/**
 * The institution's koordinatorius (O22) as the card a rep sees, never the viewer themself.
 */
class GetInstitutionCoordinator
{
    /**
     * @return array{name: string, email: string|null, profile_photo_path: string|null, duty: string|null}|null
     */
    public static function execute(Institution $institution, ?User $except = null): ?array
    {
        $manager = GetInstitutionManagers::execute($institution)
            ->first(fn (User $candidate): bool => $candidate->id !== $except?->id);

        if ($manager === null) {
            return null;
        }

        return [
            'name' => $manager->name,
            'email' => $manager->email,
            'profile_photo_path' => $manager->profile_photo_path,
            'duty' => $manager->loadMissing('current_duties')->current_duties->first()?->name,
        ];
    }
}
