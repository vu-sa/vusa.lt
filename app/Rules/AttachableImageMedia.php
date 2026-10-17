<?php

namespace App\Rules;

use App\Actions\Media\SyncImageMedia;
use App\Contracts\ImageMediaOwner;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * A posted media id must be the image the record already has, one the acting user staged, or a
 * visible image of the same kind to copy. Anything else would let a form claim another record's
 * or another person's photo.
 */
class AttachableImageMedia implements ValidationRule
{
    /**
     * @param  Model&ImageMediaOwner  $owner  the record, or an unsaved instance when creating one
     */
    public function __construct(
        protected Model&ImageMediaOwner $owner,
        protected string $collection,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User || ! is_numeric($value)) {
            $fail(__('validation.exists', ['attribute' => $attribute]));

            return;
        }

        $id = (int) $value;
        $owned = $this->owner->exists && SyncImageMedia::ownedMedia($this->owner, $this->collection, $id) !== null;

        if ($owned || SyncImageMedia::stagedMediaQuery($user, $id)->exists()) {
            return;
        }

        if (SyncImageMedia::copyableMedia($user, $this->owner, $this->collection, $id) === null) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
