<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

/**
 * The question bank is governed by the survey permissions.
 *
 * Deliberately no permission family of its own: the bank only exists to be used by
 * surveys, and a second set of seeded permissions would be one more thing for coordinators
 * to get wrong. Revisit if the bank ever needs to be delegated separately.
 */
class SurveyQuestionTemplatePolicy extends ModelPolicy
{
    use HandlesAuthorization;

    /**
     * Templates carry a single nullable `tenant`, not a `tenants` collection.
     *
     * A global template (tenant_id null) therefore matches no tenant and is only
     * manageable with the "*" scope — which is the intent: the shared bank is central.
     */
    protected bool $hasManyTenants = false;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::SURVEY->label());
    }
}
