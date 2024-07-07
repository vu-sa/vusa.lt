<?php

namespace App\Http\Controllers;

use App\Actions\GetAliasSubdomainForPublic;
use App\Models\MainPage;
use App\Models\Padalinys;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PublicController extends Controller
{
    protected Padalinys $padalinys;

    protected string $subdomain;

    public function __construct()
    {
        /**
         * Every public page requires an 'alias', which is basically the shortname of a padalinys.
         * Alias may decide in the controller, what kind of information is displayed.
         *  */
        [$alias, $subdomain] = GetAliasSubdomainForPublic::execute();

        // When we have the final alias, get the padalinys that will be used in all of the public controllers
        $this->padalinys = Padalinys::where('alias', $alias)->first();

        // We also need to use the subdomain in the public controllers
        $this->subdomain = $subdomain;

        // Subdomain and alias won't be different, except when alias = 'vusa', then subdomain = 'www'
        Inertia::share('padalinys', $this->padalinys->only(['id', 'shortname', 'alias', 'type']) +
            ['subdomain' => $subdomain]
        );
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

        Inertia::share('padalinys.banners', $banners);
    }

    protected function getPadalinysLinks()
    {
        $mainPage = MainPage::query()->where([['padalinys_id', $this->padalinys->id], ['lang', app()->getLocale()]])->orderBy('order')->get(['id', 'link', 'text']);

        Inertia::share('padalinys.links', $mainPage);
    }

    // This is mostly used for default sharing, other cases likes pages and news link to other URLs
    protected function shareOtherLangURL($name, ?string $subdomain = null, $calendarId = null)
    {
        Inertia::share('otherLangURL',
            route($name,
                [
                    'lang' => $this->getOtherLang(),
                    'calendar' => $calendarId,
                    'subdomain' => $subdomain,
                ])
        );
    }

    protected function getOtherLang()
    {
        return app()->getLocale() === 'lt' ? 'en' : 'lt';
    }
}
