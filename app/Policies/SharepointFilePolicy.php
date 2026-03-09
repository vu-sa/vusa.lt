<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\SharepointFile;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SharepointFilePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::SHAREPOINT_FILE()->label);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  SharepointFile  $sharepointFile
     */
    public function delete(User $user, Model $sharepointFile): bool
    {
        $fileable = $sharepointFile->fileables->first()?->fileable;

        // Authorize by fileable
        if ($fileable && Gate::allows('delete', [$fileable])) {
            return true;
        }

        return false;
    }
}
