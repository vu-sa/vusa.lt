<?php

namespace App\Http\Requests\Relationships;

use App\Models\Relationship;
use App\Services\RelationshipService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $relationship = $this->route('relationship');

        return $relationship instanceof Relationship
            && ($this->user()?->can('view', $relationship) ?? false);
    }

    public function rules(): array
    {
        return [
            'modelType' => ['nullable', 'string', Rule::in(RelationshipService::allowedModelAliases())],
        ];
    }
}
