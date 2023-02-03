<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\ModelAuthorizer as Authorizer;

class ResourceRequest extends FormRequest {

    protected Authorizer $authorizer;

    public function __construct() {
        $this->authorizer = new Authorizer();
    }

}