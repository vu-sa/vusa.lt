<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Services\Typesense\TypesenseManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TypesenseSearchController extends PublicController
{
    /**
     * Show the search page with initial results if query exists
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Pass any initial query from the URL to the search page
        $query = $request->input('query', '');
        
        return Inertia::render('Public/Search/Index', [
            'initialQuery' => $query,
            'typesenseConfig' => TypesenseManager::getFrontendConfig(),
        ]);
    }
}