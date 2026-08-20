<?php

namespace App\Http\Requests;

use App\Models\Navigation;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderNavigationRequest extends FormRequest
{
    /**
     * Navigation is globally scoped (see NavigationPolicy / HasCommonChecks::commonChecker) —
     * it has no tenant relation, so `update` comes only from the blanket
     * `navigation.update.all` permission and does not vary per row.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', new Navigation);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'navigation' => ['required', 'array'],
            'navigation.*.id' => ['required', 'integer', SoftDeleteRules::existsLive('navigation')],
            'navigation.*.links' => ['sometimes', 'array', 'max:3'],
            'navigation.*.links.*' => ['array'],
            'navigation.*.links.*.*.id' => ['required', 'integer', SoftDeleteRules::existsLive('navigation')],
        ];
    }
}
