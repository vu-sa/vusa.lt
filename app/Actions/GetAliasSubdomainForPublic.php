<?php

namespace App\Actions;

use App\Models\Padalinys;
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
            $subdomain = explode('.', $host)[0];
            $alias = in_array($subdomain, ['naujas', 'www', 'static']) ? 'vusa' : $subdomain;
        }

        // In some routes, the padalinys is passed as a parameter. If so, we use that, instead of the subdomain.
        if (request()->padalinys != null && in_array(request()->padalinys, ['Padaliniai', 'naujas'])) {
            $alias = 'vusa';
        }

        // When we have the final alias, get the padalinys that will be used in all of the public controllers
        return [$alias, $subdomain];
    }
}
