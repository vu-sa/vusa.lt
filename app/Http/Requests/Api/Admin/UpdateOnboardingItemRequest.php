<?php

namespace App\Http\Requests\Api\Admin;

use App\Services\OnboardingChecklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOnboardingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(OnboardingChecklist $checklist): array
    {
        $definition = $checklist->items()[$this->route('item')] ?? null;
        abort_if($definition === null, 404);

        return [
            'status' => ['required', Rule::in($definition['skippable'] ? ['pending', 'done', 'skipped'] : ['pending', 'done'])],
            'user_id' => ['prohibited'],
        ];
    }
}
