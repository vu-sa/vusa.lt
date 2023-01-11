<?php

namespace App\Http\Controllers;

use App\Policies\Traits\UseUserDutiesForAuthorization as Authorizer;


class ResourceController extends Controller
{
    protected $authorizer;
    
    public function __construct()
    {
        $this->authorizer = new Authorizer();
    }
}
