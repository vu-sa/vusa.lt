<?php

namespace App\Http\Requests\Relationships;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateModelRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('relationshipable'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scope' => 'nullable|in:within-tenant,cross-tenant',
            'bidirectional' => 'nullable|boolean',
        ];
    }
}
