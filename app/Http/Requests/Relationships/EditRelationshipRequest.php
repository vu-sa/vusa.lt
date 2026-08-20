<?php

namespace App\Http\Requests\Relationships;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EditRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('relationship'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // RelationshipService::getModelsByClass() refuses anything outside
            // AllowedRelationshipablesEnum, so an unknown class yields an empty list.
            'modelType' => 'nullable|string',
        ];
    }
}
