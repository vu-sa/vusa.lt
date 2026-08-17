<?php

namespace App\Http\Requests\Relationships;

use App\Services\RelationshipService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModelRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', $this->route('relationship'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'model_id' => 'required',
            // Feeds morphedByMany() in the controller — constrained to the models that may
            // actually take part in a relationship, never a free-form class.
            'model_type' => ['required', 'string', Rule::in(RelationshipService::allowedModelAliases())],
            'related_model_id' => 'required',
            'scope' => 'nullable|in:within-tenant,cross-tenant',
            'bidirectional' => 'nullable|boolean',
        ];
    }
}
