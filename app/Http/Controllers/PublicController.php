<?php

namespace App\Http\Controllers;

use App\Models\Padalinys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class PublicController extends Controller
{
    protected $alias;

    protected $padalinys;

    public function __construct()
    {
        // get subdomain if exists
        $host = Request::server('HTTP_HOST');

        if ($host !== 'localhost' && $host !== 'host.docker.internal:80') {
            $subdomain = explode('.', $host)[0];
            $this->alias = in_array($subdomain, ['naujas', 'www', 'static']) ? 'vusa' : $subdomain;
        } else {
            $this->alias = 'vusa';
        }

        // TODO: ???

        $requestPadalinys = request()->padalinys;

        if ($requestPadalinys != null) {
            $this->alias = in_array($requestPadalinys, ['Padaliniai', 'naujas']) ? '' : $this->alias;
        }

        Inertia::share('alias', $this->alias);

        // get padalinys
        $this->padalinys = Padalinys::where('alias', $this->alias)->first();
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
