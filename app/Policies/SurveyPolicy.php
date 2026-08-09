<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Survey;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::SURVEY->label());
    }

    /**
     * A survey that exists in LimeSurvey is frozen.
     *
     * LimeSurvey locks the structure of an activated survey, so letting the record drift
     * from what students are actually answering would be worse than refusing the edit.
     */
    #[\Override]
    public function update(User $user, Model $model): bool
    {
        if ($model instanceof Survey && ! $model->isEditable()) {
            return false;
        }

        return parent::update($user, $model);
    }

    /**
     * Deleting the vusa.lt record would orphan a live survey in LimeSurvey, leaving nobody
     * able to see who approved it.
     */
    #[\Override]
    public function delete(User $user, Model $model): bool
    {
        if ($model instanceof Survey && $model->isPublished()) {
            return false;
        }

        return parent::delete($user, $model);
    }

    /**
     * Submit a survey for approval. Anyone who may edit it may also submit it.
     */
    public function requestApproval(User $user, Survey $survey): bool
    {
        return $this->update($user, $survey);
    }

    /**
     * Re-run the LimeSurvey sync: retry a failed publish, or refresh statistics.
     *
     * Uses the base update check rather than this class's override, because the whole
     * point is to act on a survey that is already published and therefore frozen.
     */
    public function resync(User $user, Survey $survey): bool
    {
        return parent::update($user, $survey);
    }
}
