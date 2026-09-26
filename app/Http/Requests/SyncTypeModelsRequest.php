<?php

namespace App\Http\Requests;

use App\Models\Type;
use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class SyncTypeModelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type instanceof Type
            && $type->typeableRelation() !== null
            && ($this->user()?->can('update', $type) ?? false);
    }

    public function rules(): array
    {
        $relation = $this->route('type')?->typeableRelation();

        return [
            'models' => ['required', 'array'],
            'models.*' => ['string', 'distinct', SoftDeleteRules::existsLive($relation)],
        ];
    }
}
