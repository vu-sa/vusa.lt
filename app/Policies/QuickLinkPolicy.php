<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

class QuickLinkPolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    #[\Override]
    protected bool $hasManyTenants = false;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::QUICK_LINK->label());
    }
}
