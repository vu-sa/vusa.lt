<?php

namespace App\Actions;

use Illuminate\Support\Facades\Request;

class GetAliasSubdomainForPublic
{
    public static function execute()
    {
        // Get subdomain if exists
        $host = Request::server('HTTP_HOST');

        // Set default subdomain and alias
        $subdomain = 'www';
        $alias = 'vusa';

        if ($host !== 'localhost' && $host !== 'host.docker.internal:80') {
            // Change subdomain if not in local environment
            $hostParts = explode('.', $host);
            $subdomain = $hostParts[0];

            // Handle staging environment subdomains (e.g., chgf.naujas.vusa.lt)
            // Format: {tenant}.naujas.vusa.lt has 4 parts
            // Format: www.naujas.vusa.lt or naujas.vusa.lt should use 'vusa' alias
            if (count($hostParts) >= 4 && $hostParts[1] === 'naujas') {
                // chgf.naujas.vusa.lt -> subdomain=chgf, alias=chgf (unless it's www)
                // www.naujas.vusa.lt -> subdomain=www, alias=vusa
                $alias = in_array($subdomain, ['www', 'static']) ? 'vusa' : $subdomain;
            } elseif (count($hostParts) === 3 && $hostParts[0] === 'naujas') {
                // naujas.vusa.lt (no subdomain prefix) -> subdomain=naujas, alias=vusa
                $alias = 'vusa';
            } else {
                // Standard handling: www/naujas/static -> vusa, otherwise use subdomain
                $alias = in_array($subdomain, ['naujas', 'www', 'static']) ? 'vusa' : $subdomain;
            }
        }

        // In some routes, the tenant is passed as a parameter. If so, we use that, instead of the subdomain.
        if (request()->tenant != null && in_array(request()->tenant, ['Padaliniai', 'naujas'])) {
            $alias = 'vusa';
        }

        // When we have the final alias, get the tenant that will be used in all of the public controllers
        return [$alias, $subdomain];
    }
}
