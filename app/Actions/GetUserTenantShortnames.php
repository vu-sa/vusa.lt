<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;

/**
 * Shortnames of the padaliniai the user currently works in — the default "my padalinys" filter
 * of the ViSAK collections. Current duties only, never `User::tenants()`, which spans every term.
 */
class GetUserTenantShortnames
{
    /**
     * @return list<string>
     */
    public static function execute(User $user, bool $withMainTenant = false): array
    {
        $shortnames = $user->authorization_duties()
            ->with('institution.tenant:id,shortname')
            ->get()
            ->toBase()
            ->map(fn ($duty) => $duty->institution?->tenant?->shortname);

        if ($withMainTenant) {
            $shortnames->push(Tenant::main()?->shortname);
        }

        return $shortnames->filter()->unique()->values()->all();
    }
}
