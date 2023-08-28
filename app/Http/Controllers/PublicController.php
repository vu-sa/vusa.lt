<?php

namespace App\Http\Controllers;

use App\Actions\GetAliasSubdomainForPublic;
use App\Models\Padalinys;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PublicController extends Controller
{
    protected $padalinys;

    public function __construct()
    {
        /**
         * Every public page requires an 'alias', which is basically the shortname of a padalinys.
         * Alias may decide in the controller, what kind of information is displayed.
         *  */
        [$alias, $subdomain] = GetAliasSubdomainForPublic::execute();

        // When we have the final alias, get the padalinys that will be used in all of the public controllers
        $this->padalinys = Padalinys::where('alias', $alias)->first();

        // Subdomain and alias won't be different, except when alias = 'vusa', then subdomain = 'www'
        Inertia::share('padalinys', $this->padalinys->only(['id', 'shortname', 'alias', 'type']) + ['subdomain' => $subdomain]);
    }

    protected function getBanners()
    {
        $banners = Cache::remember('banners-'.$this->padalinys->id, 60 * 30, function () {

            $banners = Padalinys::where('alias', 'vusa')->first()->banners()->inRandomOrder()->where('is_active', 1)->get();

            if ($this->padalinys->type !== 'pagrindinis') {
                $banners = $this->padalinys->banners()->inRandomOrder()->where('is_active', 1)->get()->merge($banners);
            }

            return $banners;
        });

        Inertia::share('banners', $banners);
    }
}
