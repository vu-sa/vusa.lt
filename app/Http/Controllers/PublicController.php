<?php

namespace App\Http\Controllers;

use App\Models\Padalinys;
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

		if ($host !== 'localhost') {
			$subdomain = explode('.', $host)[0];
			$this->alias = in_array($subdomain, ["naujas", "www", "static"]) ? "vusa" : $subdomain;
		} else {
			$this->alias = "vusa";
		}
		
		$requestPadalinys = request()->padalinys;
		if ($requestPadalinys != null) {
			$this->alias = in_array($requestPadalinys, ["Padaliniai", "naujas"]) ? "" : $requestPadalinys;
		}

		Inertia::share('alias', $this->alias);

        // get padalinys
        $this->padalinys = Padalinys::where('alias', $this->alias)->first();
    }
}
