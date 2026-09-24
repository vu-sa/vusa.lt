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
     * @return array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null}|null
     */
    public static function execute(Institution $institution, ?User $except = null): ?array
    {
        $coordinator = GetInstitutionCoordinators::execute([$institution], $except)[0] ?? null;

        if ($coordinator === null) {
            return null;
        }

        unset($coordinator['institutions']);

        return $coordinator;
    }
}
