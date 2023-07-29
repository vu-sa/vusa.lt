<?php

namespace App\Http\Controllers;

use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;

class LaravelResourceController extends Controller
{
    protected $authorizer;

    protected $indexer;

    public function __construct()
    {
        $this->authorizer = new Authorizer();
        $this->indexer = new ModelIndexer();
    }
}
