<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Calendar;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for Calendar model authorization
 */
class CalendarPolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::CALENDAR()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $calendar): bool
    {
        return $this->commonChecker($user, $calendar, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $calendar): bool
    {
        return $this->commonChecker($user, $calendar, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $calendar): bool
    {
        return $this->commonChecker($user, $calendar, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }


}
