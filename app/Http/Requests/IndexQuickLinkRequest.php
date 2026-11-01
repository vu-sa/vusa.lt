<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexQuickLinkRequest extends BaseIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Legacy scope parameters still prefill creation and ordering.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'tenant' => 'nullable|integer|exists:tenants,id',
            'lang' => ['nullable', 'string', Rule::in(config('app.locales'))],
        ]);
    }
}
