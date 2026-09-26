<?php

namespace App\Http\Requests\Relationships;

use App\Services\RelationshipService;
use App\Support\MorphMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $modelType = $this->string('model_type')->toString();
            $modelClass = MorphMap::classFor($modelType);

            if ($modelClass === null || ! in_array($modelClass, RelationshipService::allowedModelClasses(), true)) {
                return;
            }

            foreach (['model_id', 'related_model_id'] as $attribute) {
                $id = $this->input($attribute);

                if ($id === null || $id === '') {
                    continue;
                }

                /** @var class-string<Model> $modelClass */
                if (! $modelClass::query()->whereKey($id)->exists()) {
                    $validator->errors()->add($attribute, __('validation.exists', ['attribute' => $attribute]));
                }
            }
        }];
    }
}
