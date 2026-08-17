<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Controllers\Api\Admin\AnalyticsApiController;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'period' => ['nullable', 'string', Rule::in(array_keys(AnalyticsApiController::PERIODS))],
        ];
    }
}
