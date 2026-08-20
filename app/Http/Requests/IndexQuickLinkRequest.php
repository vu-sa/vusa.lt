<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexQuickLinkRequest extends BaseIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The listing is picked by tenant + language rather than paginated, so those two params
     * are what need constraining on top of the shared index rules.
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
