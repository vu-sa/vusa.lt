<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

class MatterPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::MATTER()->label);
    }
}
