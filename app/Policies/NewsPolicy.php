<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

/**
 * Policy for News model authorization
 */
class NewsPolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    #[\Override]
    protected bool $hasManyTenants = false;

    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::NEWS->label());
    }
}
