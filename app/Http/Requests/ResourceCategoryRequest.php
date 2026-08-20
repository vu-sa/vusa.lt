<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared rules for the resource-category Store/Update pair, which were previously two
 * byte-identical classes. Only authorization differs, so each subclass supplies just that.
 *
 * Follows the parent-request pattern already used by NewsRequest, ProblemRequest and
 * InstitutionRequest.
 */
abstract class ResourceCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string',
            'description.lt' => 'nullable|string',
            'name.en' => 'nullable|string',
            'description.en' => 'nullable|string',
            'icon' => 'nullable|string',
        ];
    }
}
