<?php

namespace App\Http\Controllers;

use App\Services\ModelAuthorizer as Authorizer;

class LaravelResourceController extends Controller
{
    protected $authorizer;

    protected $indexer;

    public function __construct()
    {
        $this->authorizer = new Authorizer();
    }
}
