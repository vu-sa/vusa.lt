<?php

namespace App\Http\Requests;

use App\Models\ResourceCategory;

class StoreResourceCategoryRequest extends ResourceCategoryRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ResourceCategory::class);
    }
}
