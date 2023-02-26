<?php

namespace App\Http\Requests;

use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Foundation\Http\FormRequest;

class ResourceRequest extends FormRequest
{
    protected Authorizer $authorizer;

    public function __construct()
    {
        $this->authorizer = new Authorizer();
    }
}
