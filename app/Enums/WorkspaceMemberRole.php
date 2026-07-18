<?php

namespace App\Enums;

enum WorkspaceMemberRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Get the localized label for the workspace member role.
     */
    public function label(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Admin => $locale === 'en' ? 'Administrator' : 'Administratorius',
            self::Member => $locale === 'en' ? 'Member' : 'Narys',
        };
    }
}
