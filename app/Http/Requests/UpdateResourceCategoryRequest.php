<?php

namespace App\Http\Requests;

use App\Models\ResourceCategory;

class UpdateResourceCategoryRequest extends ResourceCategoryRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The route-bound category is authorized here as well as in the controller, so the check
     * holds whichever entry point is used. Previously this asked for the `create` ability.
     */
    public function authorize(): bool
    {
        $resourceCategory = $this->route('resourceCategory');

        return $resourceCategory instanceof ResourceCategory
            && $this->user()->can('update', $resourceCategory);
    }
}
