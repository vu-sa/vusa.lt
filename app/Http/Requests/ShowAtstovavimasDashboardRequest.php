<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowAtstovavimasDashboardRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

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
            // `?scope=tenant` and `?tab=tenant` are bookmarks from when both views shared this page.
            'scope' => ['nullable', 'string', 'max:32'],
            'tab' => ['nullable', 'string', 'max:32'],
        ];
    }
}
