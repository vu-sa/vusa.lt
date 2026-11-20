<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowSvetaineDashboardRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
        ];
    }
}
