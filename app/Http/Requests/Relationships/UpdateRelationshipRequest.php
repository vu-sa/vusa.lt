<?php

namespace App\Http\Requests\Relationships;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRelationshipRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('relationships', 'slug')->ignore($this->route('relationship'))],
            // Persisted by the controller — it used to be written with no rule at all.
            'description' => 'nullable|string',
        ];
    }
}
