<?php

namespace App\Http\Requests\Relationships;

use App\Models\Relationship;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Relationship::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:relationships,slug',
            // Persisted by the controller — it used to be written with no rule at all.
            'description' => 'nullable|string',
        ];
    }
}
